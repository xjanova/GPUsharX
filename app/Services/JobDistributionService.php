<?php

namespace App\Services;

use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\RenderJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * JobDistributionService - Parallel Job Distribution with Load Balancing
 *
 * หลักการ:
 * 1. งานเดียว (1 Job) → กระจายไปหลาย Nodes พร้อมกัน
 * 2. แบ่งงานตาม Hashrate - Node กำลังสูงได้งานเยอะกว่า
 * 3. ทุก Node ทำงานพร้อมกัน → ประกอบผลลัพธ์ที่ Server
 */
class JobDistributionService
{
    protected float $platformFeePercent = 10.0;
    protected ChunkingService $chunkingService;
    protected JobAssemblerService $assemblerService;

    public function __construct(
        ChunkingService $chunkingService,
        JobAssemblerService $assemblerService
    ) {
        $this->chunkingService = $chunkingService;
        $this->assemblerService = $assemblerService;
    }

    /**
     * Distribute jobs to all available nodes in parallel
     * แต่ละงานถูกแบ่งและกระจายไปหลาย nodes พร้อมกัน
     */
    public function distributeJobs(): int
    {
        $assignedCount = 0;

        // Get available nodes sorted by hashrate (powerful first)
        $availableNodes = GpuNode::availableForWork()
            ->orderBy('hashrate', 'desc')
            ->get();

        if ($availableNodes->isEmpty()) {
            return 0;
        }

        // Get pending jobs that need to be chunked and distributed
        $pendingJobs = RenderJob::whereIn('status', ['pending', 'queued'])
            ->where(function ($query) {
                $query->whereNull('chunking_strategy')
                    ->orWhere('total_chunks', 0);
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        // Process new jobs - chunk and distribute to all available nodes
        foreach ($pendingJobs as $job) {
            $assigned = $this->distributeJobToNodes($job, $availableNodes);
            $assignedCount += $assigned;
        }

        // Also distribute any pending chunks (already chunked jobs)
        $pendingChunks = JobChunk::pending()
            ->where('dependency_status', 'ready')
            ->whereNull('gpu_node_id')
            ->with('renderJob')
            ->limit(100)
            ->get();

        foreach ($pendingChunks as $chunk) {
            $suitableNode = $this->findSuitableNode($availableNodes, $chunk);

            if ($suitableNode) {
                $chunk->assignToNode($suitableNode);
                $assignedCount++;

                // Don't remove node from pool - allow it to take multiple chunks
                // Update parent job status
                if ($chunk->renderJob->status === 'queued') {
                    $chunk->renderJob->update([
                        'status' => 'processing',
                        'started_at' => now(),
                    ]);
                }
            }
        }

        return $assignedCount;
    }

    /**
     * Distribute a single job to multiple nodes based on their hashrate
     * กระจายงานเดียวไปหลาย nodes ตาม hashrate
     */
    public function distributeJobToNodes(RenderJob $job, Collection $availableNodes): int
    {
        if ($availableNodes->isEmpty()) {
            return 0;
        }

        // Filter nodes that meet VRAM requirements
        $suitableNodes = $availableNodes->filter(function ($node) use ($job) {
            return $node->gpu_vram_mb >= ($job->required_vram_mb ?? 0);
        });

        if ($suitableNodes->isEmpty()) {
            Log::warning("No suitable nodes for job {$job->job_id}", [
                'required_vram' => $job->required_vram_mb,
            ]);
            return 0;
        }

        // Determine chunking strategy if not set
        if (!$job->chunking_strategy) {
            $job->update([
                'chunking_strategy' => $this->determineChunkingStrategy($job),
            ]);
        }

        // Create chunks distributed by hashrate
        $chunks = $this->chunkingService->chunkJobForNodes($job, $suitableNodes);

        if (empty($chunks)) {
            return 0;
        }

        // Save chunks to database
        $createdCount = 0;
        foreach ($chunks as $chunkData) {
            $chunk = JobChunk::create($chunkData);

            // If chunk has an assigned node, update node status
            if ($chunk->gpu_node_id) {
                $node = $suitableNodes->firstWhere('id', $chunk->gpu_node_id);
                if ($node) {
                    $node->update(['status' => 'working']);
                }
                $createdCount++;
            }
        }

        // Update job status
        $job->update([
            'total_chunks' => count($chunks),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        Log::info("Job {$job->job_id} distributed to {$suitableNodes->count()} nodes", [
            'total_chunks' => count($chunks),
            'strategy' => $job->chunking_strategy,
            'nodes' => $suitableNodes->pluck('node_id')->toArray(),
        ]);

        return $createdCount;
    }

    /**
     * Determine best chunking strategy based on job type
     */
    protected function determineChunkingStrategy(RenderJob $job): string
    {
        $type = $job->type ?? 'render';
        $params = $job->job_params ?? [];

        // Image generation with upscaling → tile-based
        if (in_array($type, ['image_generation', 'upscale', 'render'])) {
            $width = $params['width'] ?? 1024;
            $height = $params['height'] ?? 1024;

            // Large images benefit from tile-based
            if ($width >= 1024 || $height >= 1024) {
                return 'tile_based';
            }
        }

        // Diffusion with many steps → step-based
        if (isset($params['steps']) && $params['steps'] > 30) {
            return 'step_based';
        }

        // Batch generation → batch-based
        if (isset($params['batch_size']) && $params['batch_size'] > 1) {
            return 'batch_based';
        }

        // Default to tile-based
        return 'tile_based';
    }

    /**
     * Find suitable node for a chunk
     */
    protected function findSuitableNode(Collection $nodes, JobChunk $chunk): ?GpuNode
    {
        $requiredVram = $chunk->renderJob->required_vram_mb ?? 0;

        // Find node with sufficient VRAM that's idle or has capacity
        return $nodes->first(function ($node) use ($requiredVram) {
            return $node->gpu_vram_mb >= $requiredVram
                && in_array($node->status, ['idle', 'online']);
        });
    }

    /**
     * Handle chunk completion - check if job ready for assembly
     */
    public function onChunkCompleted(JobChunk $chunk): void
    {
        $job = $chunk->renderJob;

        // Check if all chunks are completed
        $totalChunks = $job->total_chunks;
        $completedChunks = $job->chunks()->where('status', 'completed')->count();

        Log::info("Chunk completed: {$chunk->chunk_id}", [
            'job_id' => $job->job_id,
            'completed' => $completedChunks,
            'total' => $totalChunks,
        ]);

        // If all chunks done, trigger assembly
        if ($completedChunks >= $totalChunks) {
            $this->assemblerService->checkAndAssemble($job);
        }
    }

    /**
     * Calculate reward based on workload and performance
     */
    public function calculateReward(JobChunk $chunk, GpuNode $node): float
    {
        $baseReward = $chunk->credits_earned;

        // Workload weight bonus (based on proportion of work done)
        $workloadBonus = $baseReward * ($chunk->workload_weight ?? 0);

        // Bonus for faster completion
        $speedBonus = 0;
        if ($chunk->started_at && $chunk->completed_at) {
            $actualTime = $chunk->started_at->diffInSeconds($chunk->completed_at);
            $estimatedTime = $chunk->renderJob->estimated_time_seconds ?? 0;

            if ($estimatedTime > 0 && $actualTime < $estimatedTime * 0.8) {
                $speedBonus = $baseReward * 0.1; // 10% bonus for speed
            }
        }

        // GPU power bonus (higher hashrate = higher share)
        $totalPoolHashrate = GpuNode::online()->sum('hashrate');
        $nodeShare = $totalPoolHashrate > 0 ? ($node->hashrate / $totalPoolHashrate) : 0;
        $powerBonus = $baseReward * $nodeShare * 0.05; // Up to 5% power bonus

        return $baseReward + $workloadBonus + $speedBonus + $powerBonus;
    }

    /**
     * Re-distribute job when nodes change (join/leave)
     */
    public function rebalanceJob(RenderJob $job): array
    {
        $availableNodes = GpuNode::availableForWork()
            ->where('gpu_vram_mb', '>=', $job->required_vram_mb ?? 0)
            ->orderBy('hashrate', 'desc')
            ->get();

        return $this->chunkingService->rebalanceJob($job, $availableNodes);
    }

    /**
     * Get work for a specific node
     * Returns assigned chunks that are ready for processing
     */
    public function getWorkForNode(GpuNode $node): Collection
    {
        return JobChunk::where('gpu_node_id', $node->id)
            ->whereIn('status', ['assigned', 'pending'])
            ->where('dependency_status', 'ready')
            ->with('renderJob')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getPlatformFee(): float
    {
        return $this->platformFeePercent;
    }

    public function getPoolStatistics(): array
    {
        $totalHashrate = GpuNode::online()->sum('hashrate');

        return [
            'total_nodes' => GpuNode::count(),
            'active_nodes' => GpuNode::online()->count(),
            'total_hashrate' => $totalHashrate,
            'total_hashrate_formatted' => $this->formatHashrate($totalHashrate),
            'pending_jobs' => RenderJob::whereIn('status', ['pending', 'queued'])->count(),
            'processing_jobs' => RenderJob::where('status', 'processing')->count(),
            'assembling_jobs' => RenderJob::where('assembly_status', 'assembling')->count(),
            'pending_chunks' => JobChunk::pending()->count(),
            'processing_chunks' => JobChunk::whereIn('status', ['assigned', 'processing'])->count(),
        ];
    }

    /**
     * Get detailed job progress including all node contributions
     */
    public function getJobProgress(RenderJob $job): array
    {
        $chunks = $job->chunks()->with('gpuNode')->get();

        $nodeContributions = [];
        foreach ($chunks as $chunk) {
            $nodeId = $chunk->gpuNode?->node_id ?? 'unassigned';

            if (!isset($nodeContributions[$nodeId])) {
                $nodeContributions[$nodeId] = [
                    'node_id' => $nodeId,
                    'node_name' => $chunk->gpuNode?->name ?? 'Unassigned',
                    'hashrate' => $chunk->gpuNode?->hashrate ?? 0,
                    'chunks_total' => 0,
                    'chunks_completed' => 0,
                    'workload_weight' => 0,
                ];
            }

            $nodeContributions[$nodeId]['chunks_total']++;
            $nodeContributions[$nodeId]['workload_weight'] += $chunk->workload_weight ?? 0;

            if ($chunk->status === 'completed') {
                $nodeContributions[$nodeId]['chunks_completed']++;
            }
        }

        return [
            'job_id' => $job->job_id,
            'status' => $job->status,
            'assembly_status' => $job->assembly_status,
            'total_chunks' => $job->total_chunks,
            'completed_chunks' => $job->completed_chunks,
            'progress_percent' => $job->progress_percentage,
            'chunking_strategy' => $job->chunking_strategy,
            'node_contributions' => array_values($nodeContributions),
            'final_result_url' => $job->final_result_url,
        ];
    }

    protected function formatHashrate(float $hashrate): string
    {
        if ($hashrate >= 1000000) {
            return round($hashrate / 1000000, 2) . ' MH/s';
        } elseif ($hashrate >= 1000) {
            return round($hashrate / 1000, 2) . ' KH/s';
        }
        return round($hashrate, 2) . ' H/s';
    }
}
