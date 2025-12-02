<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpuNode;
use App\Services\SmartDistributionService;
use App\Services\WorkerPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function __construct(
        protected WorkerPerformanceService $performanceService,
        protected SmartDistributionService $distributionService
    ) {}

    /**
     * Get worker performance summary for client display
     */
    public function getPerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $summary = $this->performanceService->getWorkerPerformanceSummary($node);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get performance history
     */
    public function getPerformanceHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $days = $validated['days'] ?? 30;

        $history = $node->performanceLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'date' => $log->created_at->toDateString(),
                    'overall_score' => round($log->overall_score, 2),
                    'success_rate' => round($log->success_rate, 2),
                    'speed_score' => round($log->speed_score, 2),
                    'reliability_score' => round($log->reliability_score, 2),
                    'quality_score' => round($log->quality_score, 2),
                    'rank' => $log->rank,
                    'rank_changed' => $log->rank_changed,
                    'total_jobs' => $log->total_jobs,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'node_id' => $node->node_id,
                'history' => $history,
                'period_days' => $days,
            ],
        ]);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 100);

        $leaderboard = $this->performanceService->getLeaderboard(min($limit, 100));

        // Find user's node ranks
        $userNodes = [];
        if ($request->user()) {
            $userNodeIds = GpuNode::where('user_id', $request->user()->id)
                ->pluck('node_id')
                ->toArray();

            foreach ($leaderboard as $entry) {
                if (in_array($entry['node_id'], $userNodeIds)) {
                    $userNodes[] = $entry;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'your_nodes' => $userNodes,
                'total_workers' => GpuNode::verified()->count(),
            ],
        ]);
    }

    /**
     * Get rank information
     */
    public function getRankInfo(): JsonResponse
    {
        $ranks = [
            [
                'rank' => 'legendary',
                'name' => 'Legendary',
                'name_th' => 'ตำนาน',
                'min_score' => 95,
                'color' => '#FFD700',
                'icon' => 'crown',
                'bonus_multiplier' => 1.25,
                'description' => 'Top 1% performers with exceptional reliability and speed',
            ],
            [
                'rank' => 'master',
                'name' => 'Master',
                'name_th' => 'ปรมาจารย์',
                'min_score' => 85,
                'color' => '#9B59B6',
                'icon' => 'star',
                'bonus_multiplier' => 1.15,
                'description' => 'Excellent performance with high success rate',
            ],
            [
                'rank' => 'expert',
                'name' => 'Expert',
                'name_th' => 'ผู้เชี่ยวชาญ',
                'min_score' => 70,
                'color' => '#3498DB',
                'icon' => 'certificate',
                'bonus_multiplier' => 1.10,
                'description' => 'Very good performance and reliability',
            ],
            [
                'rank' => 'skilled',
                'name' => 'Skilled',
                'name_th' => 'ชำนาญ',
                'min_score' => 55,
                'color' => '#2ECC71',
                'icon' => 'tools',
                'bonus_multiplier' => 1.05,
                'description' => 'Good performance with consistent results',
            ],
            [
                'rank' => 'apprentice',
                'name' => 'Apprentice',
                'name_th' => 'ฝึกหัด',
                'min_score' => 40,
                'color' => '#95A5A6',
                'icon' => 'user',
                'bonus_multiplier' => 1.0,
                'description' => 'Learning and improving',
            ],
            [
                'rank' => 'novice',
                'name' => 'Novice',
                'name_th' => 'มือใหม่',
                'min_score' => 0,
                'color' => '#BDC3C7',
                'icon' => 'seedling',
                'bonus_multiplier' => 1.0,
                'description' => 'Just starting out',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $ranks,
        ]);
    }

    /**
     * Request manual evaluation
     */
    public function requestEvaluation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Check cooldown (can only request every 6 hours)
        if ($node->last_evaluation_at && $node->last_evaluation_at->diffInHours(now()) < 6) {
            $nextEval = $node->last_evaluation_at->addHours(6);
            return response()->json([
                'success' => false,
                'message' => 'Evaluation on cooldown',
                'next_available' => $nextEval->toIso8601String(),
            ], 429);
        }

        // Perform evaluation
        $result = $this->performanceService->evaluateWorker($node);

        return response()->json([
            'success' => true,
            'message' => 'Evaluation completed',
            'data' => $result,
        ]);
    }

    /**
     * Get distribution statistics
     */
    public function getDistributionStats(Request $request): JsonResponse
    {
        $days = $request->query('days', 7);

        $stats = $this->distributionService->getDistributionStats(min($days, 30));

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get all user's workers performance
     */
    public function getAllWorkersPerformance(Request $request): JsonResponse
    {
        $nodes = GpuNode::where('user_id', $request->user()->id)
            ->orderBy('performance_score', 'desc')
            ->get()
            ->map(function ($node) {
                return [
                    'node_id' => $node->node_id,
                    'name' => $node->name ?? $node->gpu_model,
                    'gpu_model' => $node->gpu_model,
                    'status' => $node->status,
                    'is_online' => $node->isOnline(),
                    'performance' => [
                        'score' => round($node->performance_score ?? 0, 2),
                        'rank' => $node->performance_rank ?? 'novice',
                        'rank_info' => $node->rank_info,
                        'success_rate' => round($node->success_rate ?? 0, 2),
                    ],
                    'statistics' => [
                        'total_jobs' => $node->total_completed_chunks ?? 0,
                        'total_earnings' => round($node->total_earnings ?? 0, 2),
                        'uptime_hours' => round($node->total_uptime_hours ?? 0, 2),
                    ],
                    'last_evaluation' => $node->last_evaluation_at?->toIso8601String(),
                ];
            });

        // Calculate aggregate stats
        $totalScore = $nodes->avg('performance.score') ?? 0;
        $totalJobs = $nodes->sum('statistics.total_jobs');
        $totalEarnings = $nodes->sum('statistics.total_earnings');

        return response()->json([
            'success' => true,
            'data' => [
                'workers' => $nodes,
                'summary' => [
                    'total_workers' => $nodes->count(),
                    'online_workers' => $nodes->where('is_online', true)->count(),
                    'avg_performance_score' => round($totalScore, 2),
                    'total_jobs_completed' => $totalJobs,
                    'total_earnings' => round($totalEarnings, 2),
                ],
            ],
        ]);
    }
}
