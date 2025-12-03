<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\GenerationJob;
use App\Models\GpuNode;
use App\Models\User;
use App\Models\Earning;
use App\Models\RenderJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Dashboard Overview - สถิติรวมทั้งหมด
     */
    public function dashboard(): JsonResponse
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_users' => User::count(),
                    'new_users_today' => User::whereDate('created_at', $today)->count(),
                    'new_users_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                    'total_nodes' => GpuNode::count(),
                    'online_nodes' => GpuNode::online()->count(),
                    'total_models' => AiModel::where('is_active', true)->count(),
                ],
                'earnings' => [
                    'total_platform_earnings' => Earning::sum('platform_fee'),
                    'today_platform_earnings' => Earning::whereDate('created_at', $today)->sum('platform_fee'),
                    'this_month_platform_earnings' => Earning::where('created_at', '>=', $thisMonth)->sum('platform_fee'),
                    'total_worker_payouts' => Earning::sum('net_amount'),
                ],
                'jobs' => [
                    'total_jobs' => GenerationJob::count(),
                    'completed_jobs' => GenerationJob::where('status', 'completed')->count(),
                    'processing_jobs' => GenerationJob::where('status', 'processing')->count(),
                    'queued_jobs' => GenerationJob::where('status', 'queued')->count(),
                    'failed_jobs' => GenerationJob::where('status', 'failed')->count(),
                    'today_jobs' => GenerationJob::whereDate('created_at', $today)->count(),
                ],
                'credits' => [
                    'total_credits_purchased' => DB::table('credit_purchases')
                        ->where('status', 'completed')
                        ->sum('credits_purchased') ?: 0,
                    'total_credits_used' => GenerationJob::sum('credits_used') ?: 0,
                    'credits_in_circulation' => User::sum('credits') ?: 0,
                ],
            ],
        ]);
    }

    /**
     * Model Analytics - สถิติแต่ละโมเดล
     */
    public function modelAnalytics(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Model usage statistics
        $modelStats = GenerationJob::select(
                'ai_model_id',
                DB::raw('COUNT(*) as total_uses'),
                DB::raw('SUM(credits_used) as total_credits'),
                DB::raw('AVG(processing_time_ms) as avg_processing_time'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('ai_model_id')
            ->get();

        // Get model details
        $models = AiModel::all()->keyBy('id');

        $modelAnalytics = $modelStats->map(function ($stat) use ($models) {
            $model = $models->get($stat->ai_model_id);
            return [
                'model_id' => $model->model_id ?? $stat->ai_model_id,
                'name' => $model->name ?? 'Unknown',
                'type' => $model->type ?? 'unknown',
                'category' => $model->category ?? 'unknown',
                'total_uses' => $stat->total_uses,
                'total_credits' => round($stat->total_credits, 2),
                'total_revenue' => round($stat->total_credits * 0.10, 2), // 10% platform fee
                'avg_processing_time_ms' => round($stat->avg_processing_time),
                'completed' => $stat->completed,
                'failed' => $stat->failed,
                'success_rate' => $stat->total_uses > 0
                    ? round(($stat->completed / $stat->total_uses) * 100, 1)
                    : 0,
            ];
        })->sortByDesc('total_uses')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'models' => $modelAnalytics,
                'summary' => [
                    'total_model_uses' => $modelStats->sum('total_uses'),
                    'total_credits_used' => round($modelStats->sum('total_credits'), 2),
                    'most_popular_model' => $modelAnalytics->first()['name'] ?? 'N/A',
                ],
            ],
        ]);
    }

    /**
     * Daily Analytics - สถิติรายวัน
     */
    public function dailyAnalytics(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        // Jobs per day
        $jobsPerDay = GenerationJob::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(credits_used) as credits')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Earnings per day
        $earningsPerDay = Earning::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(platform_fee) as platform_earnings'),
                DB::raw('SUM(net_amount) as worker_earnings'),
                DB::raw('COUNT(DISTINCT user_id) as unique_workers')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // New users per day
        $usersPerDay = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as new_users')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'jobs_per_day' => $jobsPerDay,
                'earnings_per_day' => $earningsPerDay,
                'users_per_day' => $usersPerDay,
            ],
        ]);
    }

    /**
     * Node Analytics - สถิติ Worker Nodes
     */
    public function nodeAnalytics(Request $request): JsonResponse
    {
        // Top performing nodes
        $topNodes = GpuNode::select('gpu_nodes.*')
            ->orderBy('performance_score', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($node) {
                return [
                    'node_id' => $node->node_id,
                    'name' => $node->name ?? $node->gpu_model,
                    'gpu_model' => $node->gpu_model,
                    'owner' => $node->user->name ?? 'Unknown',
                    'status' => $node->status,
                    'performance_score' => round($node->performance_score ?? 0, 2),
                    'rank' => $node->performance_rank ?? 'novice',
                    'total_jobs' => $node->total_completed_chunks ?? 0,
                    'total_earnings' => round($node->total_earnings ?? 0, 2),
                    'uptime_hours' => round($node->total_uptime_hours ?? 0, 1),
                    'is_online' => $node->isOnline(),
                ];
            });

        // Node distribution by GPU model
        $gpuDistribution = GpuNode::select('gpu_model', DB::raw('COUNT(*) as count'))
            ->groupBy('gpu_model')
            ->orderBy('count', 'desc')
            ->get();

        // Node distribution by rank
        $rankDistribution = GpuNode::select('performance_rank', DB::raw('COUNT(*) as count'))
            ->groupBy('performance_rank')
            ->get();

        // Status distribution
        $statusDistribution = GpuNode::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_nodes' => $topNodes,
                'gpu_distribution' => $gpuDistribution,
                'rank_distribution' => $rankDistribution,
                'status_distribution' => $statusDistribution,
                'summary' => [
                    'total_nodes' => GpuNode::count(),
                    'online_nodes' => GpuNode::online()->count(),
                    'verified_nodes' => GpuNode::where('is_verified', true)->count(),
                    'total_vram_gb' => round(GpuNode::sum('gpu_vram_mb') / 1024, 0),
                ],
            ],
        ]);
    }

    /**
     * User Analytics - สถิติผู้ใช้
     */
    public function userAnalytics(Request $request): JsonResponse
    {
        // Top users by credits spent
        $topSpenders = User::select('id', 'name', 'email', 'credits', 'total_earned')
            ->withCount(['generationJobs as jobs_count'])
            ->withSum('generationJobs as credits_spent', 'credits_used')
            ->orderByDesc('credits_spent')
            ->limit(20)
            ->get();

        // Top earners (workers)
        $topEarners = User::select('id', 'name', 'email', 'balance', 'total_earned')
            ->withCount(['gpuNodes as nodes_count'])
            ->orderByDesc('total_earned')
            ->limit(20)
            ->get();

        // User growth over time
        $userGrowth = User::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as new_users'),
                DB::raw('SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, "%Y-%m")) as cumulative')
            )
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->orderBy('month')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_spenders' => $topSpenders,
                'top_earners' => $topEarners,
                'user_growth' => $userGrowth,
                'summary' => [
                    'total_users' => User::count(),
                    'users_with_nodes' => User::has('gpuNodes')->count(),
                    'users_with_jobs' => User::has('generationJobs')->count(),
                    'avg_credits_per_user' => round(User::avg('credits'), 2),
                ],
            ],
        ]);
    }

    /**
     * Revenue Analytics - สถิติรายได้
     */
    public function revenueAnalytics(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // Revenue by model
        $revenueByModel = GenerationJob::select(
                'ai_model_id',
                DB::raw('SUM(credits_used) as credits'),
                DB::raw('SUM(credits_used) * 0.10 as revenue')
            )
            ->where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->groupBy('ai_model_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($item) {
                $model = AiModel::find($item->ai_model_id);
                return [
                    'model_id' => $model->model_id ?? $item->ai_model_id,
                    'model_name' => $model->name ?? 'Unknown',
                    'credits' => round($item->credits, 2),
                    'revenue' => round($item->revenue, 2),
                ];
            });

        // Daily revenue
        $dailyRevenue = Earning::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(platform_fee) as revenue'),
                DB::raw('SUM(net_amount) as payouts')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Pending payouts
        $pendingPayouts = User::where('pending_earnings', '>', 0)
            ->sum('pending_earnings');

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'revenue_by_model' => $revenueByModel,
                'daily_revenue' => $dailyRevenue,
                'summary' => [
                    'total_revenue' => round(Earning::where('created_at', '>=', $startDate)->sum('platform_fee'), 2),
                    'total_payouts' => round(Earning::where('created_at', '>=', $startDate)->sum('net_amount'), 2),
                    'pending_payouts' => round($pendingPayouts, 2),
                    'avg_daily_revenue' => round(Earning::where('created_at', '>=', $startDate)->sum('platform_fee') / $days, 2),
                ],
            ],
        ]);
    }

    /**
     * Real-time Stats - สถิติ real-time
     */
    public function realtimeStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'online_nodes' => GpuNode::online()->count(),
                'processing_jobs' => GenerationJob::where('status', 'processing')->count(),
                'queued_jobs' => GenerationJob::where('status', 'queued')->count(),
                'active_users' => User::where('updated_at', '>=', Carbon::now()->subMinutes(15))->count(),
                'jobs_last_hour' => GenerationJob::where('created_at', '>=', Carbon::now()->subHour())->count(),
                'earnings_last_hour' => round(Earning::where('created_at', '>=', Carbon::now()->subHour())->sum('platform_fee'), 2),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
