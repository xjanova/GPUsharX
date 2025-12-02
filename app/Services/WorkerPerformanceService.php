<?php

namespace App\Services;

use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\WorkerPerformanceLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * WorkerPerformanceService - ประเมินผล Worker และจัดอันดับ
 *
 * หลักการประเมิน:
 * 1. Success Rate - อัตราความสำเร็จของงาน
 * 2. Speed Score - ความเร็วเทียบกับ estimated time
 * 3. Reliability Score - ความเสถียร (uptime, ไม่ disconnect บ่อย)
 * 4. Quality Score - คุณภาพงาน (ไม่มี error, ไม่ถูก reject)
 */
class WorkerPerformanceService
{
    // Rank Thresholds
    const RANK_LEGENDARY = 95;    // Top tier
    const RANK_MASTER = 85;       // Excellent
    const RANK_EXPERT = 70;       // Very good
    const RANK_SKILLED = 55;      // Good
    const RANK_APPRENTICE = 40;   // Average
    const RANK_NOVICE = 0;        // Beginner

    // Weight factors for score calculation
    const WEIGHT_SUCCESS_RATE = 0.30;
    const WEIGHT_SPEED = 0.25;
    const WEIGHT_RELIABILITY = 0.25;
    const WEIGHT_QUALITY = 0.20;

    /**
     * ประเมินผล Worker ทั้งหมด
     */
    public function evaluateAllWorkers(): array
    {
        $nodes = GpuNode::where('is_verified', true)->get();
        $results = [];

        foreach ($nodes as $node) {
            $results[] = $this->evaluateWorker($node);
        }

        Log::info("Worker evaluation completed", [
            'total_workers' => count($results),
            'timestamp' => now()->toIso8601String(),
        ]);

        return $results;
    }

    /**
     * ประเมินผล Worker รายตัว
     */
    public function evaluateWorker(GpuNode $node, ?Carbon $periodStart = null): array
    {
        $periodStart = $periodStart ?? now()->subDays(7);
        $periodEnd = now();

        // ดึงข้อมูล chunks ในช่วงเวลา
        $chunks = JobChunk::where('gpu_node_id', $node->id)
            ->where('assigned_at', '>=', $periodStart)
            ->where('assigned_at', '<=', $periodEnd)
            ->get();

        // คำนวณ metrics
        $metrics = $this->calculateMetrics($node, $chunks, $periodStart, $periodEnd);

        // คำนวณ overall score
        $overallScore = $this->calculateOverallScore($metrics);

        // กำหนด rank
        $rank = $this->determineRank($overallScore);
        $previousRank = $node->performance_rank ?? 'novice';

        // อัพเดท node
        $node->update([
            'performance_score' => $overallScore,
            'performance_rank' => $rank,
            'success_rate' => $metrics['success_rate'],
            'avg_completion_time' => $metrics['avg_completion_time'],
            'reliability_score' => $metrics['reliability_score'],
            'quality_score' => $metrics['quality_score'],
            'last_evaluation_at' => now(),
        ]);

        // บันทึก log
        $logEntry = WorkerPerformanceLog::create([
            'gpu_node_id' => $node->id,
            'evaluation_period_start' => $periodStart,
            'evaluation_period_end' => $periodEnd,
            'total_jobs' => $metrics['total_jobs'],
            'completed_jobs' => $metrics['completed_jobs'],
            'failed_jobs' => $metrics['failed_jobs'],
            'success_rate' => $metrics['success_rate'],
            'speed_score' => $metrics['speed_score'],
            'reliability_score' => $metrics['reliability_score'],
            'quality_score' => $metrics['quality_score'],
            'overall_score' => $overallScore,
            'rank' => $rank,
            'previous_rank' => $previousRank,
            'rank_changed' => $rank !== $previousRank,
            'metrics_detail' => $metrics,
        ]);

        return [
            'node_id' => $node->node_id,
            'node_name' => $node->name,
            'metrics' => $metrics,
            'overall_score' => $overallScore,
            'rank' => $rank,
            'previous_rank' => $previousRank,
            'rank_changed' => $rank !== $previousRank,
            'log_id' => $logEntry->id,
        ];
    }

    /**
     * คำนวณ metrics จาก chunks
     */
    protected function calculateMetrics(GpuNode $node, Collection $chunks, Carbon $periodStart, Carbon $periodEnd): array
    {
        $totalJobs = $chunks->count();
        $completedJobs = $chunks->where('status', 'completed')->count();
        $failedJobs = $chunks->where('status', 'failed')->count();
        $processingJobs = $chunks->whereIn('status', ['assigned', 'processing'])->count();

        // Success Rate
        $successRate = $totalJobs > 0 ? ($completedJobs / $totalJobs) * 100 : 0;

        // Speed Score - เทียบเวลาจริงกับ estimated
        $speedScore = $this->calculateSpeedScore($chunks->where('status', 'completed'));

        // Average Completion Time
        $avgCompletionTime = $this->calculateAvgCompletionTime($chunks->where('status', 'completed'));

        // Reliability Score - uptime และความเสถียร
        $reliabilityScore = $this->calculateReliabilityScore($node, $periodStart, $periodEnd);

        // Quality Score - error rate และ rejection rate
        $qualityScore = $this->calculateQualityScore($chunks);

        return [
            'total_jobs' => $totalJobs,
            'completed_jobs' => $completedJobs,
            'failed_jobs' => $failedJobs,
            'processing_jobs' => $processingJobs,
            'success_rate' => round($successRate, 2),
            'speed_score' => round($speedScore, 2),
            'avg_completion_time' => round($avgCompletionTime, 2),
            'reliability_score' => round($reliabilityScore, 2),
            'quality_score' => round($qualityScore, 2),
            'period_start' => $periodStart->toIso8601String(),
            'period_end' => $periodEnd->toIso8601String(),
        ];
    }

    /**
     * คำนวณ Speed Score
     */
    protected function calculateSpeedScore(Collection $completedChunks): float
    {
        if ($completedChunks->isEmpty()) {
            return 50; // Default score for new workers
        }

        $scores = [];
        foreach ($completedChunks as $chunk) {
            if (!$chunk->started_at || !$chunk->completed_at) {
                continue;
            }

            $actualTime = $chunk->started_at->diffInSeconds($chunk->completed_at);
            $estimatedTime = $chunk->renderJob->estimated_time_seconds ?? 60;

            if ($estimatedTime <= 0) {
                continue;
            }

            // เร็วกว่า estimated = score สูง
            $ratio = $actualTime / $estimatedTime;

            if ($ratio <= 0.5) {
                $scores[] = 100; // เร็วมาก
            } elseif ($ratio <= 0.8) {
                $scores[] = 90; // เร็ว
            } elseif ($ratio <= 1.0) {
                $scores[] = 80; // ตามเวลา
            } elseif ($ratio <= 1.2) {
                $scores[] = 70; // ช้านิดหน่อย
            } elseif ($ratio <= 1.5) {
                $scores[] = 50; // ช้า
            } else {
                $scores[] = 30; // ช้ามาก
            }
        }

        return empty($scores) ? 50 : array_sum($scores) / count($scores);
    }

    /**
     * คำนวณเวลาเฉลี่ยในการทำงานเสร็จ
     */
    protected function calculateAvgCompletionTime(Collection $completedChunks): float
    {
        if ($completedChunks->isEmpty()) {
            return 0;
        }

        $times = [];
        foreach ($completedChunks as $chunk) {
            if ($chunk->started_at && $chunk->completed_at) {
                $times[] = $chunk->started_at->diffInSeconds($chunk->completed_at);
            }
        }

        return empty($times) ? 0 : array_sum($times) / count($times);
    }

    /**
     * คำนวณ Reliability Score
     */
    protected function calculateReliabilityScore(GpuNode $node, Carbon $periodStart, Carbon $periodEnd): float
    {
        $totalHours = $periodStart->diffInHours($periodEnd);
        if ($totalHours <= 0) {
            return 50;
        }

        // คำนวณ uptime จาก sessions
        $uptimeHours = $node->sessions()
            ->where('started_at', '>=', $periodStart)
            ->where(function ($q) use ($periodEnd) {
                $q->where('ended_at', '<=', $periodEnd)
                    ->orWhereNull('ended_at');
            })
            ->get()
            ->sum(function ($session) use ($periodEnd) {
                $end = $session->ended_at ?? $periodEnd;
                return $session->started_at->diffInHours($end);
            });

        $uptimePercent = min(100, ($uptimeHours / $totalHours) * 100);

        // นับจำนวน disconnect ผิดปกติ
        $unexpectedDisconnects = $node->sessions()
            ->where('started_at', '>=', $periodStart)
            ->whereNotNull('ended_at')
            ->where('disconnect_reason', 'unexpected')
            ->count();

        // หัก score ตาม disconnect
        $disconnectPenalty = min(30, $unexpectedDisconnects * 5);

        return max(0, min(100, $uptimePercent - $disconnectPenalty));
    }

    /**
     * คำนวณ Quality Score
     */
    protected function calculateQualityScore(Collection $chunks): float
    {
        if ($chunks->isEmpty()) {
            return 50;
        }

        $totalJobs = $chunks->count();
        $failedJobs = $chunks->where('status', 'failed')->count();
        $retriedJobs = $chunks->where('retry_count', '>', 0)->count();

        // Error rate penalty
        $errorRate = ($failedJobs / $totalJobs) * 100;
        $errorPenalty = min(40, $errorRate);

        // Retry penalty (งานที่ต้อง retry แสดงถึงปัญหา)
        $retryRate = ($retriedJobs / $totalJobs) * 100;
        $retryPenalty = min(20, $retryRate * 0.5);

        return max(0, 100 - $errorPenalty - $retryPenalty);
    }

    /**
     * คำนวณ Overall Score
     */
    protected function calculateOverallScore(array $metrics): float
    {
        return (
            ($metrics['success_rate'] * self::WEIGHT_SUCCESS_RATE) +
            ($metrics['speed_score'] * self::WEIGHT_SPEED) +
            ($metrics['reliability_score'] * self::WEIGHT_RELIABILITY) +
            ($metrics['quality_score'] * self::WEIGHT_QUALITY)
        );
    }

    /**
     * กำหนด Rank จาก Score
     */
    public function determineRank(float $score): string
    {
        if ($score >= self::RANK_LEGENDARY) {
            return 'legendary';
        } elseif ($score >= self::RANK_MASTER) {
            return 'master';
        } elseif ($score >= self::RANK_EXPERT) {
            return 'expert';
        } elseif ($score >= self::RANK_SKILLED) {
            return 'skilled';
        } elseif ($score >= self::RANK_APPRENTICE) {
            return 'apprentice';
        }
        return 'novice';
    }

    /**
     * ดึงข้อมูล Rank
     */
    public function getRankInfo(string $rank): array
    {
        $ranks = [
            'legendary' => [
                'name' => 'Legendary',
                'name_th' => 'ตำนาน',
                'min_score' => self::RANK_LEGENDARY,
                'color' => '#FFD700',
                'icon' => 'crown',
                'bonus_multiplier' => 1.25,
                'priority_boost' => 5,
            ],
            'master' => [
                'name' => 'Master',
                'name_th' => 'ปรมาจารย์',
                'min_score' => self::RANK_MASTER,
                'color' => '#9B59B6',
                'icon' => 'star',
                'bonus_multiplier' => 1.15,
                'priority_boost' => 4,
            ],
            'expert' => [
                'name' => 'Expert',
                'name_th' => 'ผู้เชี่ยวชาญ',
                'min_score' => self::RANK_EXPERT,
                'color' => '#3498DB',
                'icon' => 'certificate',
                'bonus_multiplier' => 1.10,
                'priority_boost' => 3,
            ],
            'skilled' => [
                'name' => 'Skilled',
                'name_th' => 'ชำนาญ',
                'min_score' => self::RANK_SKILLED,
                'color' => '#2ECC71',
                'icon' => 'tools',
                'bonus_multiplier' => 1.05,
                'priority_boost' => 2,
            ],
            'apprentice' => [
                'name' => 'Apprentice',
                'name_th' => 'ฝึกหัด',
                'min_score' => self::RANK_APPRENTICE,
                'color' => '#95A5A6',
                'icon' => 'user',
                'bonus_multiplier' => 1.0,
                'priority_boost' => 1,
            ],
            'novice' => [
                'name' => 'Novice',
                'name_th' => 'มือใหม่',
                'min_score' => self::RANK_NOVICE,
                'color' => '#BDC3C7',
                'icon' => 'seedling',
                'bonus_multiplier' => 1.0,
                'priority_boost' => 0,
            ],
        ];

        return $ranks[$rank] ?? $ranks['novice'];
    }

    /**
     * ดึงสรุป Performance ของ Worker สำหรับแสดงใน Client
     */
    public function getWorkerPerformanceSummary(GpuNode $node): array
    {
        $rankInfo = $this->getRankInfo($node->performance_rank ?? 'novice');

        // ดึง history 30 วัน
        $history = WorkerPerformanceLog::where('gpu_node_id', $node->id)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        // คำนวณ trend
        $trend = $this->calculateTrend($history);

        // ดึง achievements
        $achievements = $this->getWorkerAchievements($node);

        return [
            'node_id' => $node->node_id,
            'node_name' => $node->name,
            'performance' => [
                'overall_score' => round($node->performance_score ?? 0, 2),
                'rank' => $node->performance_rank ?? 'novice',
                'rank_info' => $rankInfo,
                'success_rate' => round($node->success_rate ?? 0, 2),
                'speed_score' => round($node->speed_score ?? 0, 2),
                'reliability_score' => round($node->reliability_score ?? 0, 2),
                'quality_score' => round($node->quality_score ?? 0, 2),
            ],
            'statistics' => [
                'total_jobs_completed' => $node->total_completed_chunks ?? 0,
                'total_earnings' => $node->total_earnings ?? 0,
                'avg_completion_time' => round($node->avg_completion_time ?? 0, 2),
                'uptime_hours' => round($node->total_uptime_hours ?? 0, 2),
            ],
            'trend' => $trend,
            'achievements' => $achievements,
            'last_evaluation' => $node->last_evaluation_at?->toIso8601String(),
            'next_evaluation' => $node->last_evaluation_at?->addHours(6)->toIso8601String(),
        ];
    }

    /**
     * คำนวณ trend ของ performance
     */
    protected function calculateTrend(Collection $history): array
    {
        if ($history->count() < 2) {
            return [
                'direction' => 'stable',
                'change' => 0,
                'message' => 'Not enough data',
            ];
        }

        $recent = $history->take(7)->avg('overall_score');
        $previous = $history->skip(7)->take(7)->avg('overall_score');

        if ($previous == 0) {
            return [
                'direction' => 'stable',
                'change' => 0,
                'message' => 'Performance stable',
            ];
        }

        $change = (($recent - $previous) / $previous) * 100;

        if ($change > 5) {
            return [
                'direction' => 'up',
                'change' => round($change, 2),
                'message' => 'Performance improving!',
            ];
        } elseif ($change < -5) {
            return [
                'direction' => 'down',
                'change' => round($change, 2),
                'message' => 'Performance declining',
            ];
        }

        return [
            'direction' => 'stable',
            'change' => round($change, 2),
            'message' => 'Performance stable',
        ];
    }

    /**
     * ดึง achievements ของ worker
     */
    protected function getWorkerAchievements(GpuNode $node): array
    {
        $achievements = [];

        // First Job
        if (($node->total_completed_chunks ?? 0) >= 1) {
            $achievements[] = [
                'id' => 'first_job',
                'name' => 'First Blood',
                'description' => 'Completed your first job',
                'icon' => 'trophy',
            ];
        }

        // Speed Demon
        if (($node->avg_completion_time ?? 999) < 30) {
            $achievements[] = [
                'id' => 'speed_demon',
                'name' => 'Speed Demon',
                'description' => 'Average completion under 30 seconds',
                'icon' => 'bolt',
            ];
        }

        // Perfect Week
        if (($node->success_rate ?? 0) >= 100) {
            $achievements[] = [
                'id' => 'perfect',
                'name' => 'Perfectionist',
                'description' => '100% success rate',
                'icon' => 'check-circle',
            ];
        }

        // Marathon
        if (($node->total_uptime_hours ?? 0) >= 168) {
            $achievements[] = [
                'id' => 'marathon',
                'name' => 'Marathon Runner',
                'description' => 'Over 168 hours uptime',
                'icon' => 'clock',
            ];
        }

        // Heavy Lifter
        if (($node->total_completed_chunks ?? 0) >= 1000) {
            $achievements[] = [
                'id' => 'heavy_lifter',
                'name' => 'Heavy Lifter',
                'description' => 'Completed 1000+ jobs',
                'icon' => 'dumbbell',
            ];
        }

        return $achievements;
    }

    /**
     * ดึง Leaderboard
     */
    public function getLeaderboard(int $limit = 100): array
    {
        return GpuNode::where('is_verified', true)
            ->whereNotNull('performance_score')
            ->orderBy('performance_score', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($node, $index) {
                return [
                    'rank_position' => $index + 1,
                    'node_id' => $node->node_id,
                    'node_name' => $node->name,
                    'gpu_name' => $node->gpu_name,
                    'performance_score' => round($node->performance_score, 2),
                    'performance_rank' => $node->performance_rank,
                    'success_rate' => round($node->success_rate ?? 0, 2),
                    'total_jobs' => $node->total_completed_chunks ?? 0,
                ];
            })
            ->toArray();
    }
}
