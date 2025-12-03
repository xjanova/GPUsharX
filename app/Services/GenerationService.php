<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\GenerationJob;
use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\RenderJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * GenerationService - จัดการการสร้าง Image/Video Generation Jobs
 *
 * เชื่อมต่อกับ Worker จริง ไม่มี Demo Data
 */
class GenerationService
{
    protected SmartDistributionService $distributionService;

    public function __construct(SmartDistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * สร้าง Generation Job ใหม่
     */
    public function createGenerationJob(
        User $user,
        AiModel $model,
        string $prompt,
        array $params = [],
        string $visibility = 'private'
    ): array {
        // ตรวจสอบ workers ที่พร้อมทำงาน
        $workerStatus = $this->checkWorkersAvailability($model);

        if (!$workerStatus['available']) {
            return [
                'success' => false,
                'error' => 'no_workers',
                'message' => $workerStatus['message'],
                'message_th' => $workerStatus['message_th'],
                'estimated_wait' => null,
            ];
        }

        // คำนวณ credits
        $credits = $this->calculateCredits($model, $params);

        // ตรวจสอบ credits ของ user
        if ($user->credits < $credits) {
            return [
                'success' => false,
                'error' => 'insufficient_credits',
                'message' => 'Insufficient credits',
                'message_th' => 'เครดิตไม่เพียงพอ',
                'required' => $credits,
                'available' => $user->credits,
            ];
        }

        DB::beginTransaction();
        try {
            // สร้าง GenerationJob
            $jobId = 'GEN-' . Str::upper(Str::random(12));

            $generationJob = GenerationJob::create([
                'job_id' => $jobId,
                'user_id' => $user->id,
                'ai_model_id' => $model->id,
                'type' => $model->type,
                'prompt' => $prompt,
                'negative_prompt' => $params['negative_prompt'] ?? null,
                'params' => [
                    'width' => $params['width'] ?? $model->default_params['width'] ?? 1024,
                    'height' => $params['height'] ?? $model->default_params['height'] ?? 1024,
                    'steps' => $params['steps'] ?? $model->default_params['steps'] ?? 30,
                    'cfg_scale' => $params['cfg_scale'] ?? $model->default_params['cfg_scale'] ?? 7.5,
                    'seed' => $params['seed'] ?? null,
                    'model_id' => $model->model_id,
                    'huggingface_id' => $model->huggingface_id,
                ],
                'status' => 'queued',
                'progress' => 0,
                'credits_used' => $credits,
                'visibility' => $visibility,
            ]);

            // สร้าง RenderJob สำหรับ distribution
            // รวม params ทั้งหมดที่ Worker ต้องการ
            $jobParams = array_merge($generationJob->params, [
                'prompt' => $prompt,
                'negative_prompt' => $params['negative_prompt'] ?? '',
                'generation_job_id' => $generationJob->id,
            ]);

            $renderJob = RenderJob::create([
                'job_id' => $jobId,
                'title' => Str::limit($prompt, 100),
                'type' => $model->type,
                'priority' => 'normal',
                'status' => 'pending',
                'estimated_credits' => $credits,
                'required_vram_mb' => $model->vram_required_mb,
                'estimated_time_seconds' => $this->estimateTime($model, $params),
                'job_params' => $jobParams,
                'created_by' => $user->id,
            ]);

            // หักเครดิต
            $user->decrement('credits', $credits);

            DB::commit();

            // พยายามกระจายงานทันที
            $distributionResult = $this->distributionService->distributeJob($renderJob);

            Log::info("Generation job created", [
                'job_id' => $jobId,
                'user_id' => $user->id,
                'model' => $model->model_id,
                'type' => $model->type,
                'credits' => $credits,
                'distribution' => $distributionResult['strategy'] ?? 'pending',
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                'generation_job' => $generationJob,
                'render_job' => $renderJob,
                'credits_used' => $credits,
                'estimated_time' => $workerStatus['estimated_time'],
                'workers_available' => $workerStatus['count'],
                'distribution' => $distributionResult,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create generation job", [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'error' => 'creation_failed',
                'message' => 'Failed to create job: ' . $e->getMessage(),
                'message_th' => 'ไม่สามารถสร้างงานได้',
            ];
        }
    }

    /**
     * ตรวจสอบ Workers ที่พร้อมทำงาน
     *
     * Note: ไม่เช็ค VRAM เพราะระบบมีการกระจายงานอัจฉริยะ (Smart Distribution)
     * ที่จัดการแบ่งงานให้เหมาะสมกับแต่ละ worker อยู่แล้ว
     */
    public function checkWorkersAvailability(?AiModel $model = null): array
    {
        // ดึง workers ที่ online และพร้อมทำงาน (ไม่เช็ค VRAM/RAM)
        $availableWorkers = GpuNode::availableForWork()
            ->where('is_verified', true)
            ->get();

        $totalWorkers = GpuNode::where('is_verified', true)->count();
        $onlineWorkers = GpuNode::online()->count();

        if ($availableWorkers->isEmpty()) {
            // ไม่มี worker พร้อมทำงาน
            if ($onlineWorkers === 0) {
                return [
                    'available' => false,
                    'count' => 0,
                    'total' => $totalWorkers,
                    'online' => 0,
                    'message' => 'No workers are currently online. Please try again later.',
                    'message_th' => 'ขณะนี้ไม่มี Worker ออนไลน์ กรุณาลองใหม่ภายหลัง',
                    'estimated_time' => null,
                ];
            }

            return [
                'available' => false,
                'count' => 0,
                'total' => $totalWorkers,
                'online' => $onlineWorkers,
                'message' => 'All workers are currently busy. Please wait.',
                'message_th' => 'Worker ทั้งหมดกำลังทำงานอยู่ กรุณารอสักครู่',
                'estimated_time' => null,
            ];
        }

        // คำนวณ estimated time
        $totalHashrate = $availableWorkers->sum('hashrate');
        $avgCompletionTime = $availableWorkers->avg('avg_completion_time') ?? 30;

        // ดู queue
        $queuedJobs = RenderJob::whereIn('status', ['pending', 'queued', 'processing'])->count();
        $estimatedTime = max(10, $avgCompletionTime + ($queuedJobs * 5));

        return [
            'available' => true,
            'count' => $availableWorkers->count(),
            'total' => $totalWorkers,
            'online' => $onlineWorkers,
            'total_hashrate' => $totalHashrate,
            'queue_length' => $queuedJobs,
            'estimated_time' => (int) $estimatedTime,
            'message' => "{$availableWorkers->count()} workers ready",
            'message_th' => "มี {$availableWorkers->count()} Workers พร้อมทำงาน",
        ];
    }

    /**
     * ดึงสถิติ Platform แบบ Real-time
     */
    public function getPlatformStats(): array
    {
        $totalNodes = GpuNode::where('is_verified', true)->count();
        $onlineNodes = GpuNode::online()->count();
        $availableNodes = GpuNode::availableForWork()->count();
        $workingNodes = GpuNode::where('status', 'working')->count();

        $totalHashrate = GpuNode::online()->sum('hashrate');

        $queuedJobs = RenderJob::whereIn('status', ['pending', 'queued'])->count();
        $processingJobs = RenderJob::where('status', 'processing')->count();

        // Average completion time จาก completed jobs ใน 24 ชม.
        $avgTime = GenerationJob::where('status', 'completed')
            ->where('updated_at', '>=', now()->subDay())
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms');

        $avgTimeSeconds = $avgTime ? round($avgTime / 1000, 1) : null;

        return [
            'workers' => [
                'total' => $totalNodes,
                'online' => $onlineNodes,
                'available' => $availableNodes,
                'working' => $workingNodes,
            ],
            'hashrate' => [
                'total' => $totalHashrate,
                'formatted' => $this->formatHashrate($totalHashrate),
            ],
            'queue' => [
                'pending' => $queuedJobs,
                'processing' => $processingJobs,
                'total' => $queuedJobs + $processingJobs,
            ],
            'performance' => [
                'avg_time_seconds' => $avgTimeSeconds,
                'avg_time_formatted' => $avgTimeSeconds ? "~{$avgTimeSeconds}s" : 'N/A',
            ],
            'has_workers' => $onlineNodes > 0,
            'is_ready' => $availableNodes > 0,
        ];
    }

    /**
     * คำนวณ Credits
     */
    public function calculateCredits(AiModel $model, array $params): float
    {
        // Base credits
        $baseCredits = match ($model->type) {
            'image' => 1,
            'video' => 5,
            'audio' => 2,
            '3d' => 10,
            default => 1,
        };

        // Resolution multiplier
        $width = $params['width'] ?? 1024;
        $height = $params['height'] ?? 1024;
        $pixels = $width * $height;
        $basePixels = 1024 * 1024;
        $resolutionMultiplier = max(1, $pixels / $basePixels);

        // Steps multiplier
        $steps = $params['steps'] ?? 30;
        $baseSteps = 30;
        $stepsMultiplier = max(1, $steps / $baseSteps);

        // Video frames multiplier (if applicable)
        $framesMultiplier = 1;
        if ($model->type === 'video') {
            $frames = $params['frames'] ?? 16;
            $framesMultiplier = max(1, $frames / 16);
        }

        $totalCredits = $baseCredits * $resolutionMultiplier * $stepsMultiplier * $framesMultiplier;

        return round($totalCredits, 2);
    }

    /**
     * ประมาณเวลา
     */
    protected function estimateTime(AiModel $model, array $params): int
    {
        $baseTime = match ($model->type) {
            'image' => 30,
            'video' => 120,
            'audio' => 60,
            '3d' => 180,
            default => 30,
        };

        $width = $params['width'] ?? 1024;
        $height = $params['height'] ?? 1024;
        $steps = $params['steps'] ?? 30;

        // Adjust for resolution
        $pixels = $width * $height;
        $resolutionFactor = max(1, $pixels / (1024 * 1024));

        // Adjust for steps
        $stepsFactor = max(1, $steps / 30);

        return (int) ($baseTime * $resolutionFactor * $stepsFactor);
    }

    /**
     * Format hashrate
     */
    protected function formatHashrate(float $hashrate): string
    {
        if ($hashrate >= 1000000) {
            return round($hashrate / 1000000, 2) . ' TH/s';
        } elseif ($hashrate >= 1000) {
            return round($hashrate / 1000, 2) . ' GH/s';
        }
        return round($hashrate, 2) . ' MH/s';
    }

    /**
     * อัพเดทสถานะ Generation Job จาก Worker
     */
    public function updateJobStatus(string $jobId, array $data): bool
    {
        $generationJob = GenerationJob::where('job_id', $jobId)->first();
        if (!$generationJob) {
            return false;
        }

        $updateData = [];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['progress'])) {
            $updateData['progress'] = $data['progress'];
        }

        if (isset($data['result_url'])) {
            $updateData['result_url'] = $data['result_url'];
        }

        if (isset($data['result_thumbnail'])) {
            $updateData['result_thumbnail'] = $data['result_thumbnail'];
        }

        if (isset($data['processing_time_ms'])) {
            $updateData['processing_time_ms'] = $data['processing_time_ms'];
        }

        if (isset($data['error_message'])) {
            $updateData['error_message'] = $data['error_message'];
        }

        if (isset($data['processed_by_node'])) {
            $updateData['processed_by_node'] = $data['processed_by_node'];
        }

        $generationJob->update($updateData);

        return true;
    }

    /**
     * ดึงสถานะ Job สำหรับ Client
     */
    public function getJobStatus(string $jobId): ?array
    {
        $job = GenerationJob::with(['aiModel', 'processedByNode'])
            ->where('job_id', $jobId)
            ->first();

        if (!$job) {
            return null;
        }

        $renderJob = RenderJob::where('job_id', $jobId)->first();

        return [
            'job_id' => $job->job_id,
            'status' => $job->status,
            'progress' => $job->progress,
            'type' => $job->type,
            'model' => $job->aiModel?->name,
            'result_url' => $job->result_url,
            'result_thumbnail' => $job->result_thumbnail,
            'error_message' => $job->error_message,
            'processing_time_ms' => $job->processing_time_ms,
            'credits_used' => $job->credits_used,
            'processed_by' => $job->processedByNode?->name,
            'render_job' => $renderJob ? [
                'total_chunks' => $renderJob->total_chunks,
                'completed_chunks' => $renderJob->completed_chunks,
                'assembly_status' => $renderJob->assembly_status,
            ] : null,
            'created_at' => $job->created_at->toIso8601String(),
            'updated_at' => $job->updated_at->toIso8601String(),
        ];
    }
}
