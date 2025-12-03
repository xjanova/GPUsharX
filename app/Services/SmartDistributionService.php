<?php

namespace App\Services;

use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\RenderJob;
use App\Models\DistributionLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SmartDistributionService - การจัดสรรงานอัจฉริยะ
 *
 * หลักการ:
 * 1. วิเคราะห์ลักษณะงาน (complexity, size, priority)
 * 2. วิเคราะห์ทรัพยากร (available nodes, performance score)
 * 3. ตัดสินใจแบ่งงานหรือไม่แบ่ง
 * 4. บันทึกการตัดสินใจเพื่อวิเคราะห์
 */
class SmartDistributionService
{
    protected ChunkingService $chunkingService;
    protected WorkerPerformanceService $performanceService;

    // Job complexity thresholds
    const COMPLEXITY_SIMPLE = 'simple';      // งานเบา - ให้ worker เดียวทำ
    const COMPLEXITY_MODERATE = 'moderate';  // งานปานกลาง - อาจแบ่งหรือไม่แบ่ง
    const COMPLEXITY_COMPLEX = 'complex';    // งานหนัก - ต้องแบ่ง
    const COMPLEXITY_MASSIVE = 'massive';    // งานใหญ่มาก - แบ่งเยอะ

    // Distribution strategies
    const STRATEGY_SINGLE_WORKER = 'single_worker';      // ให้ worker เดียวทำ
    const STRATEGY_PARALLEL_BALANCED = 'parallel_balanced';  // แบ่งตาม hashrate
    const STRATEGY_PARALLEL_SPEED = 'parallel_speed';    // แบ่งเน้นความเร็ว
    const STRATEGY_PARALLEL_QUALITY = 'parallel_quality'; // แบ่งเน้นคุณภาพ

    public function __construct(
        ChunkingService $chunkingService,
        WorkerPerformanceService $performanceService
    ) {
        $this->chunkingService = $chunkingService;
        $this->performanceService = $performanceService;
    }

    /**
     * กระจายงานอย่างชาญฉลาด
     */
    public function distributeJob(RenderJob $job): array
    {
        // 1. วิเคราะห์งาน
        $jobAnalysis = $this->analyzeJob($job);

        // 2. ดึง available nodes
        $availableNodes = $this->getAvailableNodes($job);

        if ($availableNodes->isEmpty()) {
            return $this->logDistribution($job, null, 'no_workers', [
                'reason' => 'No available workers',
            ]);
        }

        // 3. วิเคราะห์ทรัพยากร
        $resourceAnalysis = $this->analyzeResources($availableNodes);

        // 4. ตัดสินใจ strategy
        $decision = $this->decideStrategy($job, $jobAnalysis, $resourceAnalysis, $availableNodes);

        // 5. ดำเนินการตาม strategy
        $result = $this->executeStrategy($job, $decision, $availableNodes);

        // 6. บันทึก log
        return $this->logDistribution($job, $decision, 'success', $result);
    }

    /**
     * วิเคราะห์ลักษณะงาน
     */
    protected function analyzeJob(RenderJob $job): array
    {
        $params = $job->job_params ?? [];
        $type = $job->type ?? 'render';

        // คำนวณ complexity score
        $complexityScore = 0;

        // Image size factor
        $width = $params['width'] ?? 512;
        $height = $params['height'] ?? 512;
        $pixels = $width * $height;

        if ($pixels >= 4194304) { // 2048x2048
            $complexityScore += 40;
        } elseif ($pixels >= 1048576) { // 1024x1024
            $complexityScore += 25;
        } elseif ($pixels >= 262144) { // 512x512
            $complexityScore += 10;
        }

        // Steps factor
        $steps = $params['steps'] ?? 20;
        if ($steps >= 100) {
            $complexityScore += 30;
        } elseif ($steps >= 50) {
            $complexityScore += 20;
        } elseif ($steps >= 30) {
            $complexityScore += 10;
        }

        // Batch size factor
        $batchSize = $params['batch_size'] ?? 1;
        $complexityScore += min(30, $batchSize * 5);

        // Model size factor (if specified)
        $modelSize = $params['model_size'] ?? 'base';
        if ($modelSize === 'xl') {
            $complexityScore += 20;
        } elseif ($modelSize === 'large') {
            $complexityScore += 10;
        }

        // VRAM requirement
        $requiredVram = $job->required_vram_mb ?? 4000;
        if ($requiredVram >= 16000) {
            $complexityScore += 20;
        } elseif ($requiredVram >= 8000) {
            $complexityScore += 10;
        }

        // กำหนด complexity level
        $complexity = $this->determineComplexity($complexityScore);

        // ประมาณเวลา
        $estimatedTime = $this->estimateJobTime($job, $params);

        return [
            'type' => $type,
            'complexity_score' => $complexityScore,
            'complexity' => $complexity,
            'estimated_time_seconds' => $estimatedTime,
            'pixels' => $pixels,
            'steps' => $steps,
            'batch_size' => $batchSize,
            'required_vram' => $requiredVram,
            'priority' => $job->priority ?? 'normal',
            'can_be_chunked' => $this->canBeChunked($job),
            'recommended_chunks' => $this->recommendChunkCount($job, $complexityScore),
        ];
    }

    /**
     * กำหนด complexity level
     */
    protected function determineComplexity(int $score): string
    {
        if ($score >= 80) {
            return self::COMPLEXITY_MASSIVE;
        } elseif ($score >= 50) {
            return self::COMPLEXITY_COMPLEX;
        } elseif ($score >= 25) {
            return self::COMPLEXITY_MODERATE;
        }
        return self::COMPLEXITY_SIMPLE;
    }

    /**
     * ประมาณเวลางาน
     */
    protected function estimateJobTime(RenderJob $job, array $params): int
    {
        $baseTime = 30; // วินาที

        $pixels = ($params['width'] ?? 512) * ($params['height'] ?? 512);
        $steps = $params['steps'] ?? 20;
        $batchSize = $params['batch_size'] ?? 1;

        // สูตรประมาณ (ปรับตามข้อมูลจริง)
        $time = $baseTime;
        $time += ($pixels / 262144) * 10; // +10s per 512x512 pixels
        $time += ($steps / 20) * 15; // +15s per 20 steps
        $time *= $batchSize;

        return (int) $time;
    }

    /**
     * ตรวจสอบว่างานแบ่งได้หรือไม่
     */
    protected function canBeChunked(RenderJob $job): bool
    {
        $type = $job->type ?? 'render';
        $params = $job->job_params ?? [];

        // บางงานแบ่งไม่ได้
        if (isset($params['no_chunk']) && $params['no_chunk']) {
            return false;
        }

        // งาน inpainting อาจแบ่งยาก
        if ($type === 'inpainting' && !isset($params['tile_mode'])) {
            return false;
        }

        return true;
    }

    /**
     * แนะนำจำนวน chunks
     */
    protected function recommendChunkCount(RenderJob $job, int $complexityScore): int
    {
        if ($complexityScore < 25) {
            return 1; // งานเบา - ไม่แบ่ง
        } elseif ($complexityScore < 50) {
            return 2; // งานปานกลาง
        } elseif ($complexityScore < 80) {
            return 4; // งานหนัก
        }
        return 8; // งานใหญ่มาก
    }

    /**
     * ดึง available nodes พร้อม filter ตาม VRAM requirement
     *
     * สำคัญ: ต้องเช็ค VRAM ของ node ให้พอสำหรับงาน
     * - ถ้างานต้องการ VRAM > node มี → node นั้นทำไม่ได้
     * - ถ้างานถูกแบ่ง (chunked) → แต่ละ chunk จะมี required_vram_mb ของตัวเอง
     */
    protected function getAvailableNodes(RenderJob $job): Collection
    {
        $requiredVram = $job->required_vram_mb ?? 4000;
        $modelId = $job->job_params['model_id'] ?? $job->model_id ?? null;

        $query = GpuNode::availableForWork()
            ->where('is_verified', true);

        // Filter by VRAM - node ต้องมี VRAM พอสำหรับงาน
        // แต่ถ้าเป็น admin test job ให้ข้ามการเช็ค
        $isAdminTest = $job->job_params['is_admin_test'] ?? false;
        if (!$isAdminTest) {
            $query->where('gpu_vram_mb', '>=', $requiredVram);
        }

        // Filter by installed models (ถ้ามี model_id)
        if ($modelId && !$isAdminTest) {
            $query->where(function ($q) use ($modelId) {
                // Node ต้องมี model ติดตั้ง หรือ installed_models เป็น null (accept all)
                $q->whereNull('installed_models')
                  ->orWhereJsonContains('installed_models', $modelId);
            });
        }

        return $query
            ->orderBy('performance_score', 'desc')
            ->orderBy('hashrate', 'desc')
            ->get();
    }

    /**
     * ดึง nodes ที่รองรับ VRAM ระดับต่างๆ (สำหรับ smart chunking)
     */
    protected function getNodesByVramTier(RenderJob $job): array
    {
        $allNodes = GpuNode::availableForWork()
            ->where('is_verified', true)
            ->orderBy('gpu_vram_mb', 'desc')
            ->get();

        return [
            'tier_24gb' => $allNodes->filter(fn($n) => ($n->gpu_vram_mb ?? 0) >= 24000),
            'tier_12gb' => $allNodes->filter(fn($n) => ($n->gpu_vram_mb ?? 0) >= 12000 && ($n->gpu_vram_mb ?? 0) < 24000),
            'tier_8gb' => $allNodes->filter(fn($n) => ($n->gpu_vram_mb ?? 0) >= 8000 && ($n->gpu_vram_mb ?? 0) < 12000),
            'tier_6gb' => $allNodes->filter(fn($n) => ($n->gpu_vram_mb ?? 0) >= 6000 && ($n->gpu_vram_mb ?? 0) < 8000),
            'tier_4gb' => $allNodes->filter(fn($n) => ($n->gpu_vram_mb ?? 0) >= 4000 && ($n->gpu_vram_mb ?? 0) < 6000),
            'tier_3gb' => $allNodes->filter(fn($n) => ($n->gpu_vram_mb ?? 0) >= 3000 && ($n->gpu_vram_mb ?? 0) < 4000),
        ];
    }

    /**
     * วิเคราะห์ทรัพยากรที่มี
     */
    protected function analyzeResources(Collection $nodes): array
    {
        $totalHashrate = $nodes->sum('hashrate');
        $avgPerformanceScore = $nodes->avg('performance_score') ?? 50;

        // แบ่งกลุ่ม nodes ตาม rank
        $nodesByRank = $nodes->groupBy('performance_rank');

        // หา top performers
        $topPerformers = $nodes->filter(fn($n) => ($n->performance_score ?? 0) >= 70);

        // หา reliable nodes
        $reliableNodes = $nodes->filter(fn($n) => ($n->reliability_score ?? 0) >= 80);

        return [
            'total_nodes' => $nodes->count(),
            'total_hashrate' => $totalHashrate,
            'avg_performance_score' => round($avgPerformanceScore, 2),
            'nodes_by_rank' => $nodesByRank->map->count()->toArray(),
            'top_performers_count' => $topPerformers->count(),
            'reliable_nodes_count' => $reliableNodes->count(),
            'has_high_tier' => $topPerformers->isNotEmpty(),
            'best_node' => $nodes->first(),
        ];
    }

    /**
     * ตัดสินใจ strategy
     */
    protected function decideStrategy(
        RenderJob $job,
        array $jobAnalysis,
        array $resourceAnalysis,
        Collection $availableNodes
    ): array {
        $complexity = $jobAnalysis['complexity'];
        $canBeChunked = $jobAnalysis['can_be_chunked'];
        $priority = $jobAnalysis['priority'];
        $estimatedTime = $jobAnalysis['estimated_time_seconds'];

        // Decision factors
        $factors = [];

        // Factor 1: Job complexity
        $factors['complexity'] = $complexity;

        // Factor 2: มี nodes กี่ตัว
        $factors['node_count'] = $resourceAnalysis['total_nodes'];

        // Factor 3: งานแบ่งได้ไหม
        $factors['can_chunk'] = $canBeChunked;

        // Factor 4: Priority ของงาน
        $factors['priority'] = $priority;

        // Decision Logic

        // Case 1: งานง่าย + มี top performer → ให้คนเดียวทำ
        if ($complexity === self::COMPLEXITY_SIMPLE) {
            $bestNode = $this->findBestSingleWorker($availableNodes, $job);
            return [
                'strategy' => self::STRATEGY_SINGLE_WORKER,
                'reason' => 'Simple job - single worker is efficient',
                'reason_th' => 'งานเบา - ให้ worker คนเดียวทำได้',
                'selected_nodes' => [$bestNode],
                'chunk_count' => 1,
                'factors' => $factors,
            ];
        }

        // Case 2: งานปานกลาง - พิจารณาตามเงื่อนไข
        if ($complexity === self::COMPLEXITY_MODERATE) {
            // ถ้า estimated time < 60 วินาที และมี reliable worker → ให้คนเดียวทำ
            if ($estimatedTime < 60 && $resourceAnalysis['reliable_nodes_count'] > 0) {
                $bestNode = $this->findBestSingleWorker($availableNodes, $job);
                return [
                    'strategy' => self::STRATEGY_SINGLE_WORKER,
                    'reason' => 'Moderate job with short time - single worker preferred',
                    'reason_th' => 'งานปานกลางใช้เวลาไม่นาน - ให้ worker คนเดียวทำ',
                    'selected_nodes' => [$bestNode],
                    'chunk_count' => 1,
                    'factors' => $factors,
                ];
            }

            // ถ้ามี nodes น้อยกว่า 3 ตัว → ไม่แบ่ง
            if ($resourceAnalysis['total_nodes'] < 3) {
                $bestNode = $this->findBestSingleWorker($availableNodes, $job);
                return [
                    'strategy' => self::STRATEGY_SINGLE_WORKER,
                    'reason' => 'Few nodes available - single worker more efficient',
                    'reason_th' => 'มี nodes น้อย - ให้ worker คนเดียวทำดีกว่า',
                    'selected_nodes' => [$bestNode],
                    'chunk_count' => 1,
                    'factors' => $factors,
                ];
            }
        }

        // Case 3: งานหนัก/ใหญ่มาก + แบ่งได้ → Parallel
        if ($canBeChunked && $resourceAnalysis['total_nodes'] >= 2) {
            $chunkCount = $this->calculateOptimalChunkCount(
                $jobAnalysis,
                $resourceAnalysis,
                $availableNodes
            );

            $selectedNodes = $this->selectNodesForParallel($availableNodes, $chunkCount, $job);

            // เลือก strategy ย่อย
            $subStrategy = self::STRATEGY_PARALLEL_BALANCED;
            if ($priority === 'urgent') {
                $subStrategy = self::STRATEGY_PARALLEL_SPEED;
            } elseif ($job->type === 'upscale' || $job->type === 'render') {
                $subStrategy = self::STRATEGY_PARALLEL_QUALITY;
            }

            return [
                'strategy' => $subStrategy,
                'reason' => "Complex job ({$complexity}) - parallel processing recommended",
                'reason_th' => "งานซับซ้อน ({$complexity}) - แบ่งให้หลาย workers ทำพร้อมกัน",
                'selected_nodes' => $selectedNodes,
                'chunk_count' => $chunkCount,
                'factors' => $factors,
            ];
        }

        // Default: Single worker
        $bestNode = $this->findBestSingleWorker($availableNodes, $job);
        return [
            'strategy' => self::STRATEGY_SINGLE_WORKER,
            'reason' => 'Default strategy - single worker',
            'reason_th' => 'Strategy เริ่มต้น - ให้ worker คนเดียวทำ',
            'selected_nodes' => [$bestNode],
            'chunk_count' => 1,
            'factors' => $factors,
        ];
    }

    /**
     * หา best single worker
     */
    protected function findBestSingleWorker(Collection $nodes, RenderJob $job): GpuNode
    {
        // เรียงตาม: 1. performance_score, 2. success_rate, 3. hashrate
        return $nodes
            ->sortByDesc(function ($node) {
                return (
                    ($node->performance_score ?? 0) * 0.5 +
                    ($node->success_rate ?? 0) * 0.3 +
                    min(100, ($node->hashrate ?? 0) / 1000) * 0.2
                );
            })
            ->first();
    }

    /**
     * คำนวณจำนวน chunks ที่เหมาะสม
     */
    protected function calculateOptimalChunkCount(
        array $jobAnalysis,
        array $resourceAnalysis,
        Collection $nodes
    ): int {
        $recommendedChunks = $jobAnalysis['recommended_chunks'];
        $availableNodes = $resourceAnalysis['total_nodes'];

        // ไม่ควรแบ่งมากกว่าจำนวน nodes
        $maxChunks = min($recommendedChunks, $availableNodes);

        // ถ้างานใหญ่มาก อาจให้ 1 node ทำหลาย chunks
        if ($jobAnalysis['complexity'] === self::COMPLEXITY_MASSIVE) {
            $maxChunks = min($recommendedChunks, $availableNodes * 2);
        }

        return max(1, $maxChunks);
    }

    /**
     * เลือก nodes สำหรับ parallel processing
     */
    protected function selectNodesForParallel(Collection $nodes, int $count, RenderJob $job): Collection
    {
        // เรียงตาม combined score (ไม่เช็ค VRAM เพราะงานจะถูกแบ่งให้เหมาะสม)
        $sorted = $nodes->sortByDesc(function ($node) {
            return (
                ($node->performance_score ?? 50) * 0.4 +
                ($node->hashrate ?? 0) / 100 * 0.3 +
                ($node->reliability_score ?? 50) * 0.3
            );
        });

        return $sorted->take($count);
    }

    /**
     * ดำเนินการตาม strategy
     */
    protected function executeStrategy(RenderJob $job, array $decision, Collection $availableNodes): array
    {
        $strategy = $decision['strategy'];
        $selectedNodes = collect($decision['selected_nodes']);
        $chunkCount = $decision['chunk_count'];

        switch ($strategy) {
            case self::STRATEGY_SINGLE_WORKER:
                return $this->executeSingleWorker($job, $selectedNodes->first());

            case self::STRATEGY_PARALLEL_BALANCED:
            case self::STRATEGY_PARALLEL_SPEED:
            case self::STRATEGY_PARALLEL_QUALITY:
                return $this->executeParallel($job, $selectedNodes, $chunkCount, $strategy);

            default:
                return $this->executeSingleWorker($job, $selectedNodes->first());
        }
    }

    /**
     * Execute single worker strategy
     */
    protected function executeSingleWorker(RenderJob $job, GpuNode $node): array
    {
        // สร้าง single chunk
        $chunk = JobChunk::create([
            'chunk_id' => "{$job->job_id}_single_0",
            'render_job_id' => $job->id,
            'gpu_node_id' => $node->id,
            'chunk_type' => 'full',
            'chunk_index' => 0,
            'chunk_config' => [
                'strategy' => 'single_worker',
                'full_job' => true,
            ],
            'workload_weight' => 1.0,
            'dependency_status' => 'ready',
            'credits_earned' => $job->estimated_credits ?? 100,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Update job
        $job->update([
            'chunking_strategy' => 'single_worker',
            'total_chunks' => 1,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Update node status
        $node->update(['status' => 'working']);

        return [
            'chunks_created' => 1,
            'nodes_assigned' => 1,
            'assignment' => [
                [
                    'chunk_id' => $chunk->chunk_id,
                    'node_id' => $node->node_id,
                    'node_name' => $node->name,
                    'workload' => '100%',
                ],
            ],
        ];
    }

    /**
     * Execute parallel strategy
     */
    protected function executeParallel(RenderJob $job, Collection $nodes, int $chunkCount, string $strategy): array
    {
        // กำหนด chunking strategy
        $chunkingStrategy = $this->determineChunkingStrategy($job, $strategy);

        $job->update([
            'chunking_strategy' => $chunkingStrategy,
            'parallel_config' => array_merge($job->parallel_config ?? [], [
                'distribution_strategy' => $strategy,
            ]),
        ]);

        // ใช้ ChunkingService สร้าง chunks
        $chunks = $this->chunkingService->chunkJobForNodes($job, $nodes);

        // Save chunks
        $assignment = [];
        foreach ($chunks as $chunkData) {
            $chunk = JobChunk::create($chunkData);

            $node = $nodes->firstWhere('id', $chunk->gpu_node_id);
            if ($node) {
                $node->update(['status' => 'working']);

                $assignment[] = [
                    'chunk_id' => $chunk->chunk_id,
                    'node_id' => $node->node_id,
                    'node_name' => $node->name,
                    'chunk_index' => $chunk->chunk_index,
                    'workload' => round($chunk->workload_weight * 100, 1) . '%',
                ];
            }
        }

        // Update job
        $job->update([
            'total_chunks' => count($chunks),
            'status' => 'processing',
            'started_at' => now(),
        ]);

        return [
            'chunks_created' => count($chunks),
            'nodes_assigned' => $nodes->count(),
            'assignment' => $assignment,
        ];
    }

    /**
     * กำหนด chunking strategy จาก distribution strategy
     */
    protected function determineChunkingStrategy(RenderJob $job, string $distributionStrategy): string
    {
        $type = $job->type ?? 'render';
        $params = $job->job_params ?? [];

        // ถ้า parallel speed → ใช้ tile-based เพราะแบ่งง่าย
        if ($distributionStrategy === self::STRATEGY_PARALLEL_SPEED) {
            return 'tile_based';
        }

        // ถ้า parallel quality → ใช้ batch-based
        if ($distributionStrategy === self::STRATEGY_PARALLEL_QUALITY) {
            if (($params['batch_size'] ?? 1) > 1) {
                return 'batch_based';
            }
            return 'tile_based';
        }

        // Default logic
        if (in_array($type, ['image_generation', 'upscale', 'render'])) {
            $width = $params['width'] ?? 1024;
            $height = $params['height'] ?? 1024;

            if ($width >= 1024 || $height >= 1024) {
                return 'tile_based';
            }
        }

        if (isset($params['steps']) && $params['steps'] > 30) {
            return 'step_based';
        }

        if (isset($params['batch_size']) && $params['batch_size'] > 1) {
            return 'batch_based';
        }

        return 'tile_based';
    }

    /**
     * บันทึก Distribution Log
     */
    protected function logDistribution(RenderJob $job, ?array $decision, string $status, array $result): array
    {
        $logData = [
            'render_job_id' => $job->id,
            'job_id' => $job->job_id,
            'job_type' => $job->type,
            'job_priority' => $job->priority,
            'status' => $status,
            'strategy' => $decision['strategy'] ?? null,
            'reason' => $decision['reason'] ?? null,
            'reason_th' => $decision['reason_th'] ?? null,
            'factors' => $decision['factors'] ?? null,
            'chunks_created' => $result['chunks_created'] ?? 0,
            'nodes_assigned' => $result['nodes_assigned'] ?? 0,
            'assignment_details' => $result['assignment'] ?? null,
            'created_at' => now(),
        ];

        $log = DistributionLog::create($logData);

        Log::info("Smart distribution completed", [
            'job_id' => $job->job_id,
            'strategy' => $decision['strategy'] ?? 'none',
            'status' => $status,
            'log_id' => $log->id,
        ]);

        return array_merge($result, [
            'log_id' => $log->id,
            'strategy' => $decision['strategy'] ?? null,
            'reason' => $decision['reason'] ?? null,
            'reason_th' => $decision['reason_th'] ?? null,
        ]);
    }

    /**
     * ดึงสถิติการกระจายงาน
     */
    public function getDistributionStats(int $days = 7): array
    {
        $since = now()->subDays($days);

        $logs = DistributionLog::where('created_at', '>=', $since)->get();

        $byStrategy = $logs->groupBy('strategy')->map->count();
        $avgChunks = $logs->avg('chunks_created');
        $avgNodes = $logs->avg('nodes_assigned');

        return [
            'total_distributions' => $logs->count(),
            'by_strategy' => $byStrategy->toArray(),
            'avg_chunks_per_job' => round($avgChunks, 2),
            'avg_nodes_per_job' => round($avgNodes, 2),
            'single_worker_percent' => round(
                ($byStrategy->get('single_worker', 0) / max(1, $logs->count())) * 100,
                2
            ),
            'parallel_percent' => round(
                (($logs->count() - $byStrategy->get('single_worker', 0)) / max(1, $logs->count())) * 100,
                2
            ),
            'period_days' => $days,
        ];
    }
}
