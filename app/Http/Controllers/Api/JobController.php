<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\GenerationJob;
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

        // Get installed models on this node
        $installedModels = $node->installed_models ?? [];
        $nodeVram = $node->gpu_vram_mb ?? 8192;

        // Find new work - get chunks assigned to this node or pending chunks
        // Now checks VRAM at chunk level (supports low VRAM workers)
        $pendingChunks = JobChunk::where(function ($q) use ($node) {
                // Already assigned to this node
                $q->where('gpu_node_id', $node->id)
                    ->where('status', 'pending')
                    ->where('dependency_status', 'ready');
            })
            ->orWhere(function ($q) use ($node, $installedModels, $nodeVram) {
                // Unassigned pending chunks
                $q->whereNull('gpu_node_id')
                    ->where('status', 'pending')
                    ->where('dependency_status', 'ready')
                    // Check VRAM at CHUNK level first (for smart-split chunks)
                    ->where(function ($vq) use ($nodeVram) {
                        $vq->whereNull('required_vram_mb') // No specific requirement
                           ->orWhere('required_vram_mb', '<=', $nodeVram);
                    })
                    ->whereHas('renderJob', function ($rq) use ($node, $installedModels, $nodeVram) {
                        // Fallback: Check job-level VRAM if chunk doesn't have specific requirement
                        // OR allow admin test jobs (bypass VRAM check)
                        $rq->where(function ($vq) use ($nodeVram) {
                            $vq->where('required_vram_mb', '<=', $nodeVram)
                               ->orWhereJsonContains('job_params->is_admin_test', true);
                        });

                        // Check if worker has the required model installed
                        // Skip this check for admin tests or if worker has no models installed (accept all)
                        if (!empty($installedModels)) {
                            $rq->where(function ($mq) use ($installedModels) {
                                // Match model_id in job_params with installed models
                                foreach ($installedModels as $modelId) {
                                    $mq->orWhereJsonContains('job_params->model_id', $modelId);
                                }
                                // Also allow admin test jobs to bypass model check
                                $mq->orWhereJsonContains('job_params->is_admin_test', true);
                            });
                        }
                    });
            })
            ->with('renderJob')
            ->orderBy('required_vram_mb', 'asc') // Prefer smaller chunks for fairness
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

        // Update GenerationJob if exists (for AI generation jobs)
        $generationJobId = $job->job_params['generation_job_id'] ?? null;
        if ($generationJobId) {
            $this->updateGenerationJob($job, $generationJobId, $finalResultUrl, $node);
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
            ->with('renderJob')
            ->firstOrFail();

        $chunk->markAsFailed($validated['error_message']);

        // ตรวจสอบว่า Job fail ทั้งหมดหรือไม่ (retry หมดแล้ว)
        $job = $chunk->renderJob;
        $refunded = false;
        $refundAmount = 0;

        if ($this->isJobCompletelyFailed($job)) {
            $refundResult = $this->refundCreditsForFailedJob($job, $validated['error_message']);
            $refunded = $refundResult['refunded'];
            $refundAmount = $refundResult['amount'];
        }

        return response()->json([
            'success' => true,
            'message' => 'Error reported, chunk returned to queue',
            'data' => [
                'refunded' => $refunded,
                'refund_amount' => $refundAmount,
            ],
        ]);
    }

    protected function formatChunk(JobChunk $chunk): array
    {
        $jobParams = $chunk->renderJob->job_params ?? [];

        return [
            'chunk_id' => $chunk->chunk_id,
            'job_id' => $chunk->renderJob->job_id,
            'job_title' => $chunk->renderJob->title,
            'job_type' => $chunk->renderJob->type,
            'chunk_index' => $chunk->chunk_index,
            'total_chunks' => $chunk->renderJob->total_chunks,
            'status' => $chunk->status,
            'params' => $chunk->chunk_params,
            'job_params' => $jobParams,
            'credits' => $chunk->credits_earned,
            'assigned_at' => $chunk->assigned_at?->toIso8601String(),
            // Parallel Processing fields
            'chunk_type' => $chunk->chunk_type,
            'chunk_config' => $chunk->chunk_config,
            'workload_weight' => $chunk->workload_weight,
            'chunking_strategy' => $chunk->renderJob->chunking_strategy,
            // Generation specific fields for Worker
            'generation' => [
                'prompt' => $jobParams['prompt'] ?? null,
                'negative_prompt' => $jobParams['negative_prompt'] ?? '',
                'model_id' => $jobParams['model_id'] ?? null,
                'huggingface_id' => $jobParams['huggingface_id'] ?? null,
                'width' => $jobParams['width'] ?? 1024,
                'height' => $jobParams['height'] ?? 1024,
                'steps' => $jobParams['steps'] ?? 30,
                'cfg_scale' => $jobParams['cfg_scale'] ?? 7.5,
                'seed' => $jobParams['seed'] ?? null,
            ],
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

    /**
     * Update GenerationJob when RenderJob completes
     */
    protected function updateGenerationJob(RenderJob $renderJob, int $generationJobId, ?string $finalResultUrl, GpuNode $node): void
    {
        $generationJob = GenerationJob::find($generationJobId);
        if (!$generationJob) {
            return;
        }

        $totalChunks = $renderJob->total_chunks ?? 1;
        $completedChunks = $renderJob->completed_chunks ?? 0;

        // Calculate progress
        $progress = $totalChunks > 0 ? round(($completedChunks / $totalChunks) * 100) : 0;

        $updateData = [
            'progress' => $progress,
            'processed_by_node' => $node->id,
        ];

        // Update status based on RenderJob status
        if ($renderJob->status === 'completed' || $renderJob->assembly_status === 'completed') {
            $updateData['status'] = 'completed';
            $updateData['progress'] = 100;

            // Set result URL
            if ($finalResultUrl) {
                $updateData['result_url'] = $finalResultUrl;
                $updateData['result_thumbnail'] = $finalResultUrl; // Can be same or generate thumbnail
            }

            // Calculate processing time
            if ($generationJob->created_at) {
                $updateData['processing_time_ms'] = $generationJob->created_at->diffInMilliseconds(now());
            }
        } elseif ($renderJob->status === 'processing') {
            $updateData['status'] = 'processing';
        } elseif ($renderJob->status === 'failed') {
            $updateData['status'] = 'failed';
            $updateData['error_message'] = 'Job processing failed';
        }

        $generationJob->update($updateData);
    }

    /**
     * ตรวจสอบว่า Job ล้มเหลวทั้งหมดหรือไม่
     * Job ถือว่า fail เมื่อ retry ครบแล้วหรือ chunks ทั้งหมด fail
     */
    protected function isJobCompletelyFailed(RenderJob $job): bool
    {
        $job->refresh();

        // ถ้า job status เป็น failed แล้ว
        if ($job->status === 'failed') {
            return true;
        }

        // ตรวจสอบว่า chunks ทั้งหมด fail หรือไม่
        $totalChunks = $job->total_chunks ?? 0;
        if ($totalChunks === 0) {
            return false;
        }

        $failedChunks = $job->chunks()->where('status', 'failed')->count();
        $maxRetries = config('gpu.max_chunk_retries', 3);

        // ถ้า fail เกิน retry limit
        $chunksExceededRetries = $job->chunks()
            ->where('status', 'failed')
            ->where('retry_count', '>=', $maxRetries)
            ->count();

        // ถ้ามี chunk ที่ retry หมดแล้ว → job fail
        if ($chunksExceededRetries > 0) {
            $job->update(['status' => 'failed']);
            return true;
        }

        return false;
    }

    /**
     * คืนเครดิตให้ User เมื่องานล้มเหลว
     */
    protected function refundCreditsForFailedJob(RenderJob $job, string $errorMessage): array
    {
        $generationJobId = $job->job_params['generation_job_id'] ?? null;

        if (!$generationJobId) {
            return ['refunded' => false, 'amount' => 0];
        }

        $generationJob = GenerationJob::find($generationJobId);
        if (!$generationJob) {
            return ['refunded' => false, 'amount' => 0];
        }

        // ถ้าคืนเงินไปแล้ว ไม่ต้องคืนซ้ำ
        if ($generationJob->status === 'refunded') {
            return ['refunded' => false, 'amount' => 0];
        }

        $creditsToRefund = $generationJob->credits_used;

        // คืนเครดิตให้ User
        $user = $generationJob->user;
        if ($user && $creditsToRefund > 0) {
            $user->increment('credits', $creditsToRefund);

            // อัพเดท GenerationJob
            $generationJob->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'refunded_at' => now(),
                'refunded_amount' => $creditsToRefund,
            ]);

            // บันทึก log
            \Illuminate\Support\Facades\Log::info("Refunded credits for failed job", [
                'job_id' => $job->job_id,
                'generation_job_id' => $generationJobId,
                'user_id' => $user->id,
                'credits_refunded' => $creditsToRefund,
                'error' => $errorMessage,
            ]);

            return ['refunded' => true, 'amount' => $creditsToRefund];
        }

        return ['refunded' => false, 'amount' => 0];
    }
}
