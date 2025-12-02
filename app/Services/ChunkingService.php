<?php

namespace App\Services;

use App\Models\RenderJob;
use App\Models\JobChunk;
use App\Models\GpuNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ChunkingService - แบ่งงานตามกำลังของแต่ละ Node
 *
 * หลักการ: แบ่งงานให้ Balance ตาม Hashrate
 * - Node กำลังสูง (hashrate มาก) → ได้งานเยอะกว่า
 * - Node กำลังต่ำ (hashrate น้อย) → ได้งานน้อยกว่า
 * - ทุก Node ควรเสร็จพร้อมกัน (หรือใกล้เคียง)
 */
class ChunkingService
{
    /**
     * แบ่งงานให้ Nodes ตาม Hashrate
     *
     * @param RenderJob $job
     * @param Collection $availableNodes
     * @return array
     */
    public function chunkJobForNodes(RenderJob $job, Collection $availableNodes): array
    {
        if ($availableNodes->isEmpty()) {
            return [];
        }

        return match ($job->chunking_strategy) {
            'tile_based' => $this->chunkByTilesBalanced($job, $availableNodes),
            'step_based' => $this->chunkByStepsBalanced($job, $availableNodes),
            'batch_based' => $this->chunkByBatchBalanced($job, $availableNodes),
            'hybrid' => $this->chunkHybridBalanced($job, $availableNodes),
            default => $this->chunkByTilesBalanced($job, $availableNodes),
        };
    }

    /**
     * Tile-based chunking พร้อม Load Balancing
     * แบ่ง Tiles ให้ Node ตาม Hashrate
     */
    private function chunkByTilesBalanced(RenderJob $job, Collection $nodes): array
    {
        $config = $job->parallel_config ?? [];
        $width = $config['width'] ?? 1024;
        $height = $config['height'] ?? 1024;
        $tileSize = $config['tile_size'] ?? 512;
        $overlap = $config['overlap'] ?? 64;

        // 1. สร้างรายการ Tiles ทั้งหมด
        $tiles = $this->generateTiles($width, $height, $tileSize, $overlap);
        $totalTiles = count($tiles);

        // 2. คำนวณ Total Hashrate
        $totalHashrate = $nodes->sum('hashrate');
        if ($totalHashrate == 0) {
            $totalHashrate = $nodes->count(); // Fallback: ใช้จำนวน nodes
        }

        // 3. แบ่ง Tiles ให้แต่ละ Node ตาม Hashrate proportion
        $chunks = [];
        $tileIndex = 0;

        foreach ($nodes as $node) {
            // คำนวณจำนวน Tiles ที่ Node นี้ควรได้
            $nodeHashrate = $node->hashrate ?: 1;
            $proportion = $nodeHashrate / $totalHashrate;
            $tilesForNode = max(1, round($totalTiles * $proportion));

            // สร้าง Chunks สำหรับ Node นี้
            for ($i = 0; $i < $tilesForNode && $tileIndex < $totalTiles; $i++) {
                $tile = $tiles[$tileIndex];

                $chunks[] = [
                    'chunk_id' => "{$job->job_id}_tile_{$tileIndex}",
                    'render_job_id' => $job->id,
                    'chunk_type' => 'tile',
                    'chunk_index' => $tileIndex,
                    'chunk_config' => $tile,
                    'gpu_node_id' => $node->id,
                    'workload_weight' => $proportion,
                    'dependency_status' => 'ready', // Tiles ไม่มี dependency
                    'credits_earned' => $this->calculateChunkCredits($job, $proportion),
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ];

                $tileIndex++;
            }
        }

        // 4. กระจาย Tiles ที่เหลือให้ Nodes ที่แรงสุด
        $sortedNodes = $nodes->sortByDesc('hashrate');
        while ($tileIndex < $totalTiles) {
            foreach ($sortedNodes as $node) {
                if ($tileIndex >= $totalTiles) break;

                $tile = $tiles[$tileIndex];
                $proportion = ($node->hashrate ?: 1) / $totalHashrate;

                $chunks[] = [
                    'chunk_id' => "{$job->job_id}_tile_{$tileIndex}",
                    'render_job_id' => $job->id,
                    'chunk_type' => 'tile',
                    'chunk_index' => $tileIndex,
                    'chunk_config' => $tile,
                    'gpu_node_id' => $node->id,
                    'workload_weight' => $proportion,
                    'dependency_status' => 'ready',
                    'credits_earned' => $this->calculateChunkCredits($job, $proportion),
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ];

                $tileIndex++;
            }
        }

        return $chunks;
    }

    /**
     * Step-based chunking พร้อม Load Balancing
     * Node แรงกว่าได้ Steps มากกว่า
     */
    private function chunkByStepsBalanced(RenderJob $job, Collection $nodes): array
    {
        $config = $job->parallel_config ?? [];
        $totalSteps = $config['total_steps'] ?? 50;

        // คำนวณ Total Hashrate
        $totalHashrate = $nodes->sum('hashrate');
        if ($totalHashrate == 0) {
            $totalHashrate = $nodes->count();
        }

        $chunks = [];
        $currentStep = 1;
        $prevChunkId = null;
        $index = 0;

        // เรียง Nodes ตาม Hashrate (แรงสุดทำก่อน เพราะ step-based ต้องรอกัน)
        $sortedNodes = $nodes->sortByDesc('hashrate');

        foreach ($sortedNodes as $node) {
            if ($currentStep > $totalSteps) break;

            $nodeHashrate = $node->hashrate ?: 1;
            $proportion = $nodeHashrate / $totalHashrate;

            // Node แรงกว่าได้ Steps มากกว่า
            $stepsForNode = max(1, round($totalSteps * $proportion));
            $endStep = min($currentStep + $stepsForNode - 1, $totalSteps);

            $chunkId = "{$job->job_id}_steps_{$index}";

            $chunks[] = [
                'chunk_id' => $chunkId,
                'render_job_id' => $job->id,
                'chunk_type' => 'step_range',
                'chunk_index' => $index,
                'chunk_config' => [
                    'start_step' => $currentStep,
                    'end_step' => $endStep,
                    'total_steps' => $totalSteps,
                ],
                'gpu_node_id' => $node->id,
                'workload_weight' => $proportion,
                'depends_on_chunk_id' => $prevChunkId,
                'dependency_status' => $prevChunkId ? 'waiting' : 'ready',
                'credits_earned' => $this->calculateChunkCredits($job, $proportion),
                'status' => $prevChunkId ? 'pending' : 'assigned',
                'assigned_at' => $prevChunkId ? null : now(),
            ];

            $prevChunkId = $chunkId;
            $currentStep = $endStep + 1;
            $index++;
        }

        return $chunks;
    }

    /**
     * Batch-based chunking พร้อม Load Balancing
     * แบ่ง Batch items ตาม Hashrate
     */
    private function chunkByBatchBalanced(RenderJob $job, Collection $nodes): array
    {
        $config = $job->parallel_config ?? [];
        $batchSize = $config['batch_size'] ?? 4;
        $seeds = $config['seeds'] ?? range(1, $batchSize);

        $totalHashrate = $nodes->sum('hashrate');
        if ($totalHashrate == 0) {
            $totalHashrate = $nodes->count();
        }

        $chunks = [];
        $itemIndex = 0;

        foreach ($nodes as $node) {
            if ($itemIndex >= $batchSize) break;

            $nodeHashrate = $node->hashrate ?: 1;
            $proportion = $nodeHashrate / $totalHashrate;

            // Node แรงกว่าได้ items มากกว่า
            $itemsForNode = max(1, round($batchSize * $proportion));

            for ($i = 0; $i < $itemsForNode && $itemIndex < $batchSize; $i++) {
                $chunks[] = [
                    'chunk_id' => "{$job->job_id}_batch_{$itemIndex}",
                    'render_job_id' => $job->id,
                    'chunk_type' => 'batch_item',
                    'chunk_index' => $itemIndex,
                    'chunk_config' => [
                        'batch_index' => $itemIndex,
                        'seed' => $seeds[$itemIndex] ?? ($itemIndex + 1),
                    ],
                    'gpu_node_id' => $node->id,
                    'workload_weight' => $proportion,
                    'dependency_status' => 'ready', // Batch items ไม่มี dependency
                    'credits_earned' => $this->calculateChunkCredits($job, 1.0 / $batchSize),
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ];

                $itemIndex++;
            }
        }

        return $chunks;
    }

    /**
     * Hybrid chunking
     * Phase 1: Generate low-res with steps
     * Phase 2: Upscale with tiles
     */
    private function chunkHybridBalanced(RenderJob $job, Collection $nodes): array
    {
        // สำหรับ hybrid ใช้ tile-based เป็นหลัก
        return $this->chunkByTilesBalanced($job, $nodes);
    }

    /**
     * Generate tile regions
     */
    private function generateTiles(int $width, int $height, int $tileSize, int $overlap): array
    {
        $tiles = [];
        $effectiveSize = $tileSize - $overlap;

        for ($y = 0; $y < $height; $y += $effectiveSize) {
            for ($x = 0; $x < $width; $x += $effectiveSize) {
                $tiles[] = [
                    'x' => $x,
                    'y' => $y,
                    'width' => min($tileSize, $width - $x + $overlap),
                    'height' => min($tileSize, $height - $y + $overlap),
                    'overlap' => $overlap,
                ];
            }
        }

        return $tiles;
    }

    /**
     * คำนวณ Credits สำหรับ Chunk
     */
    private function calculateChunkCredits(RenderJob $job, float $proportion): int
    {
        $totalCredits = $job->estimated_credits ?? 100;
        return max(1, (int) round($totalCredits * $proportion));
    }

    /**
     * คำนวณเวลาโดยประมาณที่แต่ละ Node ควรใช้
     * เพื่อให้ทุก Node เสร็จพร้อมกัน
     */
    public function estimateCompletionTimes(RenderJob $job, Collection $nodes): array
    {
        $config = $job->parallel_config ?? [];
        $totalWork = $this->estimateTotalWork($job);

        $totalHashrate = $nodes->sum('hashrate');
        $estimates = [];

        foreach ($nodes as $node) {
            $nodeHashrate = $node->hashrate ?: 1;
            $proportion = $nodeHashrate / $totalHashrate;
            $workForNode = $totalWork * $proportion;

            // เวลา = งาน / กำลัง
            $estimatedSeconds = $workForNode / $nodeHashrate;

            $estimates[$node->node_id] = [
                'proportion' => $proportion,
                'work_units' => $workForNode,
                'estimated_seconds' => round($estimatedSeconds, 2),
            ];
        }

        return $estimates;
    }

    /**
     * ประมาณการงานทั้งหมด (work units)
     */
    private function estimateTotalWork(RenderJob $job): float
    {
        $config = $job->parallel_config ?? [];

        return match ($job->chunking_strategy) {
            'tile_based' => ($config['width'] ?? 1024) * ($config['height'] ?? 1024) / 1000,
            'step_based' => $config['total_steps'] ?? 50,
            'batch_based' => $config['batch_size'] ?? 4,
            default => 100,
        };
    }

    /**
     * Re-balance chunks เมื่อมี Node ใหม่เข้ามา หรือ Node หลุด
     */
    public function rebalanceJob(RenderJob $job, Collection $newNodes): array
    {
        // ดึง chunks ที่ยังไม่เสร็จ
        $pendingChunks = $job->chunks()
            ->whereIn('status', ['pending', 'assigned'])
            ->get();

        if ($pendingChunks->isEmpty() || $newNodes->isEmpty()) {
            return [];
        }

        $totalHashrate = $newNodes->sum('hashrate');
        $reassignments = [];

        // Reset chunks ที่ยังไม่เริ่มทำ
        foreach ($pendingChunks as $chunk) {
            if ($chunk->status === 'assigned' && !$chunk->started_at) {
                $chunk->update([
                    'gpu_node_id' => null,
                    'status' => 'pending',
                ]);
            }
        }

        // Re-assign ตาม hashrate ใหม่
        $pendingChunks = $job->chunks()
            ->where('status', 'pending')
            ->orderBy('chunk_index')
            ->get();

        $chunkIndex = 0;
        foreach ($newNodes as $node) {
            $nodeHashrate = $node->hashrate ?: 1;
            $proportion = $nodeHashrate / $totalHashrate;
            $chunksForNode = max(1, round($pendingChunks->count() * $proportion));

            for ($i = 0; $i < $chunksForNode && $chunkIndex < $pendingChunks->count(); $i++) {
                $chunk = $pendingChunks[$chunkIndex];
                $chunk->update([
                    'gpu_node_id' => $node->id,
                    'workload_weight' => $proportion,
                    'status' => 'assigned',
                ]);

                $reassignments[] = [
                    'chunk_id' => $chunk->chunk_id,
                    'node_id' => $node->node_id,
                ];

                $chunkIndex++;
            }
        }

        return $reassignments;
    }
}
