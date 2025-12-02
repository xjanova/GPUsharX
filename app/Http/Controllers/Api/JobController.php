<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\RenderJob;
use App\Services\AntiCheatService;
use App\Services\JobAssemblerService;
use App\Services\JobDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    public function __construct(
        protected JobDistributionService $distributionService,
        protected AntiCheatService $antiCheatService,
        protected JobAssemblerService $assemblerService
    ) {}

    public function getWork(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($node->status, ['online', 'idle', 'working'])) {
            return response()->json([
                'success' => false,
                'message' => "Node status is '{$node->status}', cannot receive work",
            ], 400);
        }

        // Check for already assigned chunks (can have multiple in parallel mode)
        $assignedChunks = JobChunk::where('gpu_node_id', $node->id)
            ->whereIn('status', ['assigned', 'processing'])
            ->where('dependency_status', 'ready')
            ->with('renderJob')
            ->get();

        if ($assignedChunks->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_work' => true,
                    'chunks' => $assignedChunks->map(fn($c) => $this->formatChunk($c))->toArray(),
                    'chunk' => $this->formatChunk($assignedChunks->first()), // Backwards compatibility
                ],
            ]);
        }

        // Find new work - get chunks assigned to this node or pending chunks
        $pendingChunks = JobChunk::where(function ($q) use ($node) {
                // Already assigned to this node
                $q->where('gpu_node_id', $node->id)
                    ->where('status', 'pending')
                    ->where('dependency_status', 'ready');
            })
            ->orWhere(function ($q) use ($node) {
                // Unassigned pending chunks
                $q->whereNull('gpu_node_id')
                    ->where('status', 'pending')
                    ->where('dependency_status', 'ready')
                    ->whereHas('renderJob', function ($rq) use ($node) {
                        $rq->where('required_vram_mb', '<=', $node->gpu_vram_mb);
                    });
            })
            ->with('renderJob')
            ->limit(5) // Allow node to take multiple chunks
            ->get();

        if ($pendingChunks->isNotEmpty()) {
            $chunks = [];
            foreach ($pendingChunks as $chunk) {
                $chunk->assignToNode($node);

                if ($chunk->renderJob->status === 'queued') {
                    $chunk->renderJob->update([
                        'status' => 'processing',
                        'started_at' => now(),
                    ]);
                }

                $chunks[] = $this->formatChunk($chunk->fresh('renderJob'));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_work' => true,
                    'chunks' => $chunks,
                    'chunk' => $chunks[0] ?? null, // Backwards compatibility
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_work' => false,
                'message' => 'No work available at this time',
            ],
        ]);
    }

    public function startWork(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'chunk_id' => 'required|string|exists:job_chunks,chunk_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $chunk = JobChunk::where('chunk_id', $validated['chunk_id'])
            ->where('gpu_node_id', $node->id)
            ->where('status', 'assigned')
            ->firstOrFail();

        $chunk->markAsProcessing();

        return response()->json([
            'success' => true,
            'message' => 'Work started',
        ]);
    }

    public function updateProgress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'chunk_id' => 'required|string|exists:job_chunks,chunk_id',
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $chunk = JobChunk::where('chunk_id', $validated['chunk_id'])
            ->where('gpu_node_id', $node->id)
            ->where('status', 'processing')
            ->firstOrFail();

        $chunk->update(['progress' => $validated['progress']]);

        return response()->json([
            'success' => true,
            'data' => [
                'progress' => $chunk->progress,
            ],
        ]);
    }

    public function submitWork(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'chunk_id' => 'required|string|exists:job_chunks,chunk_id',
            'result_hash' => 'required|string|size:64',
            'result_file' => 'nullable|string',
            'partial_result_url' => 'nullable|string', // For parallel processing
            'metadata' => 'nullable|array',
        ]);

        $user = $request->user();
        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $chunk = JobChunk::where('chunk_id', $validated['chunk_id'])
            ->where('gpu_node_id', $node->id)
            ->where('status', 'processing')
            ->with('renderJob')
            ->firstOrFail();

        // Validate work result
        $validation = $this->antiCheatService->validateWorkResult(
            $chunk,
            $validated['result_hash'],
            $validated['metadata'] ?? []
        );

        if (!$validation['valid']) {
            $chunk->markAsFailed(implode(', ', $validation['issues']));

            return response()->json([
                'success' => false,
                'message' => 'Work validation failed',
                'errors' => $validation['issues'],
            ], 422);
        }

        // Complete the chunk with partial result URL for assembly
        $chunk->markAsCompleted(
            $validated['result_hash'],
            $validated['result_file'] ?? null,
            $validated['partial_result_url'] ?? null
        );

        // Calculate and create earning
        $reward = $this->distributionService->calculateReward($chunk, $node);
        $earning = Earning::createJobReward(
            $user,
            $node,
            $chunk,
            $reward,
            $this->distributionService->getPlatformFee()
        );

        // Update session stats if exists
        $session = $node->sessions()->whereNull('ended_at')->first();
        if ($session) {
            $session->addCompletedChunk($earning->net_amount);
        }

        // Check if job is ready for assembly (all chunks completed)
        $job = $chunk->renderJob->fresh();
        $assemblyTriggered = false;
        $finalResultUrl = null;

        if ($job->assembly_status === 'ready') {
            // Trigger assembly process
            $this->assemblerService->checkAndAssemble($job);
            $assemblyTriggered = true;

            // If assembly completed immediately, get the result
            $job->refresh();
            if ($job->assembly_status === 'completed') {
                $finalResultUrl = $job->final_result_url;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Work submitted successfully',
            'data' => [
                'earned' => [
                    'gross' => $earning->amount,
                    'platform_fee' => $earning->platform_fee,
                    'net' => $earning->net_amount,
                ],
                'user_balance' => [
                    'pending' => $user->fresh()->pending_earnings,
                    'available' => $user->fresh()->balance,
                ],
                'job_progress' => [
                    'completed_chunks' => $job->completed_chunks,
                    'total_chunks' => $job->total_chunks,
                    'progress_percent' => $job->progress_percentage,
                    'assembly_status' => $job->assembly_status,
                    'assembly_triggered' => $assemblyTriggered,
                    'final_result_url' => $finalResultUrl,
                ],
            ],
        ]);
    }

    /**
     * Upload partial result file for chunk
     */
    public function uploadPartialResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'chunk_id' => 'required|string|exists:job_chunks,chunk_id',
            'file' => 'required|file|max:102400', // 100MB max
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $chunk = JobChunk::where('chunk_id', $validated['chunk_id'])
            ->where('gpu_node_id', $node->id)
            ->where('status', 'processing')
            ->with('renderJob')
            ->firstOrFail();

        // Store the partial result file
        $file = $request->file('file');
        $path = "partial_results/{$chunk->renderJob->job_id}";
        $filename = "{$chunk->chunk_id}_{$chunk->chunk_index}." . $file->getClientOriginalExtension();

        $storedPath = $file->storeAs($path, $filename, 'public');
        $url = Storage::disk('public')->url($storedPath);

        // Save partial result URL to chunk
        $chunk->savePartialResult($url, [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Partial result uploaded',
            'data' => [
                'url' => $url,
                'chunk_id' => $chunk->chunk_id,
            ],
        ]);
    }

    /**
     * Get job progress including all node contributions
     */
    public function getJobProgress(Request $request, string $jobId): JsonResponse
    {
        $job = RenderJob::where('job_id', $jobId)->firstOrFail();

        // Verify user has access (either job creator or node owner working on it)
        $user = $request->user();
        $hasAccess = $job->created_by === $user->id ||
            $job->chunks()->whereHas('gpuNode', fn($q) => $q->where('user_id', $user->id))->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $progress = $this->distributionService->getJobProgress($job);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    public function reportError(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'chunk_id' => 'required|string|exists:job_chunks,chunk_id',
            'error_message' => 'required|string|max:1000',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $chunk = JobChunk::where('chunk_id', $validated['chunk_id'])
            ->where('gpu_node_id', $node->id)
            ->whereIn('status', ['assigned', 'processing'])
            ->firstOrFail();

        $chunk->markAsFailed($validated['error_message']);

        return response()->json([
            'success' => true,
            'message' => 'Error reported, chunk returned to queue',
        ]);
    }

    protected function formatChunk(JobChunk $chunk): array
    {
        return [
            'chunk_id' => $chunk->chunk_id,
            'job_id' => $chunk->renderJob->job_id,
            'job_title' => $chunk->renderJob->title,
            'job_type' => $chunk->renderJob->type,
            'chunk_index' => $chunk->chunk_index,
            'total_chunks' => $chunk->renderJob->total_chunks,
            'status' => $chunk->status,
            'params' => $chunk->chunk_params,
            'job_params' => $chunk->renderJob->job_params,
            'credits' => $chunk->credits_earned,
            'assigned_at' => $chunk->assigned_at?->toIso8601String(),
            // Parallel Processing fields
            'chunk_type' => $chunk->chunk_type,
            'chunk_config' => $chunk->chunk_config,
            'workload_weight' => $chunk->workload_weight,
            'chunking_strategy' => $chunk->renderJob->chunking_strategy,
        ];
    }

    public function getPoolStats(): JsonResponse
    {
        $stats = $this->distributionService->getPoolStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
