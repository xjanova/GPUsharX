<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\RenderJob;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VramController extends Controller
{
    /**
     * VRAM tier colors for UI
     */
    private const TIER_COLORS = [
        'ultra_low' => '#dc3545',  // Red
        'very_low' => '#fd7e14',   // Orange
        'low' => '#ffc107',        // Yellow
        'medium' => '#20c997',     // Teal
        'high' => '#0d6efd',       // Blue
        'ultra' => '#6f42c1',      // Purple
    ];

    /**
     * Display VRAM management page
     */
    public function index(): View
    {
        // Get VRAM tiers with worker counts
        $vramTiers = $this->getVramTiersWithStats();

        // Get stats
        $stats = [
            'total_workers' => GpuNode::count(),
            'online_workers' => GpuNode::online()->count(),
            'low_vram_workers' => GpuNode::where('gpu_vram_mb', '<=', 6144)->count(),
            'pending_chunks' => JobChunk::where('status', 'pending')->count(),
            'micro_chunks' => JobChunk::where('chunk_type', 'micro')->where('status', 'pending')->count(),
        ];

        // Get workers sorted by VRAM
        $workers = GpuNode::with('user')
            ->orderBy('gpu_vram_mb', 'asc')
            ->paginate(20);

        // Get active jobs with chunking info
        $activeJobs = RenderJob::whereIn('status', ['queued', 'processing'])
            ->withCount('chunks')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get settings
        $settings = [
            'min_vram_mb' => SiteSetting::get('min_vram_mb', 4096),
            'enable_micro_chunks' => SiteSetting::get('enable_micro_chunks', true),
            'enable_step_splitting' => SiteSetting::get('enable_step_splitting', true),
            'max_chunks_per_job' => SiteSetting::get('max_chunks_per_job', 64),
            'low_vram_bonus_percent' => SiteSetting::get('low_vram_bonus_percent', 10),
        ];

        return view('admin.vram-management', compact(
            'vramTiers',
            'stats',
            'workers',
            'activeJobs',
            'settings'
        ));
    }

    /**
     * Get VRAM tiers with statistics
     */
    private function getVramTiersWithStats(): array
    {
        $tiers = [];

        foreach (RenderJob::VRAM_TIERS as $tierName => $tierConfig) {
            $workers = GpuNode::where('gpu_vram_mb', '>=', $tierConfig['min_mb'])
                ->where('gpu_vram_mb', '<=', $tierConfig['max_mb'])
                ->get();

            $onlineCount = $workers->where('status', 'online')->count();
            $workingCount = $workers->where('status', 'working')->count();
            $totalCount = $workers->count();

            // Calculate utilization
            $utilization = $totalCount > 0
                ? round(($workingCount / $totalCount) * 100)
                : 0;

            $tiers[$tierName] = [
                'label' => $tierConfig['label'],
                'min_mb' => $tierConfig['min_mb'],
                'max_mb' => $tierConfig['max_mb'],
                'max_resolution' => $tierConfig['max_resolution'],
                'max_steps_per_chunk' => $tierConfig['max_steps_per_chunk'],
                'supported_models' => $tierConfig['supported_models'],
                'precision' => $tierConfig['precision'],
                'worker_count' => $totalCount,
                'online_count' => $onlineCount,
                'working_count' => $workingCount,
                'utilization' => $utilization,
                'color' => self::TIER_COLORS[$tierName] ?? '#666',
            ];
        }

        return $tiers;
    }

    /**
     * Update VRAM settings
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_vram_mb' => 'required|integer|min:2048|max:16384',
            'enable_micro_chunks' => 'nullable',
            'enable_step_splitting' => 'nullable',
            'max_chunks_per_job' => 'required|integer|min:1|max:256',
            'low_vram_bonus_percent' => 'required|integer|min:0|max:50',
        ]);

        SiteSetting::set('min_vram_mb', $validated['min_vram_mb']);
        SiteSetting::set('enable_micro_chunks', $request->has('enable_micro_chunks'));
        SiteSetting::set('enable_step_splitting', $request->has('enable_step_splitting'));
        SiteSetting::set('max_chunks_per_job', $validated['max_chunks_per_job']);
        SiteSetting::set('low_vram_bonus_percent', $validated['low_vram_bonus_percent']);

        return redirect()->back()->with('success', 'VRAM settings updated successfully!');
    }

    /**
     * API: Get VRAM statistics
     */
    public function getStats(): \Illuminate\Http\JsonResponse
    {
        $tiers = $this->getVramTiersWithStats();

        $stats = [
            'total_workers' => GpuNode::count(),
            'online_workers' => GpuNode::online()->count(),
            'tiers' => $tiers,
            'chunks' => [
                'pending' => JobChunk::where('status', 'pending')->count(),
                'processing' => JobChunk::where('status', 'processing')->count(),
                'by_type' => [
                    'micro' => JobChunk::where('chunk_type', 'micro')->count(),
                    'tile' => JobChunk::whereJsonContains('chunk_params->type', 'tile')->count(),
                    'step' => JobChunk::whereJsonContains('chunk_params->type', 'step_range')->count(),
                    'hybrid' => JobChunk::whereJsonContains('chunk_params->type', 'hybrid')->count(),
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * API: Get workers by VRAM tier
     */
    public function getWorkersByTier(string $tier): \Illuminate\Http\JsonResponse
    {
        if (!isset(RenderJob::VRAM_TIERS[$tier])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tier',
            ], 400);
        }

        $config = RenderJob::VRAM_TIERS[$tier];

        $workers = GpuNode::where('gpu_vram_mb', '>=', $config['min_mb'])
            ->where('gpu_vram_mb', '<=', $config['max_mb'])
            ->with('user')
            ->get()
            ->map(function ($worker) {
                return [
                    'id' => $worker->id,
                    'node_id' => $worker->node_id,
                    'name' => $worker->name,
                    'gpu_model' => $worker->gpu_model,
                    'gpu_vram_mb' => $worker->gpu_vram_mb,
                    'status' => $worker->status,
                    'user' => $worker->user ? $worker->user->name : 'N/A',
                    'total_completed' => $worker->total_completed_chunks,
                    'total_earnings' => $worker->total_earnings,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'tier' => $tier,
                'config' => $config,
                'workers' => $workers,
            ],
        ]);
    }

    /**
     * Force re-split a job for low VRAM workers
     */
    public function resplitJob(Request $request, int $jobId): RedirectResponse
    {
        $job = RenderJob::findOrFail($jobId);

        if (!in_array($job->status, ['pending', 'queued'])) {
            return redirect()->back()->with('error', 'Can only re-split pending or queued jobs');
        }

        // Delete existing chunks
        $job->chunks()->delete();

        // Re-split with smart algorithm
        $job->smartSplit();

        return redirect()->back()->with('success', "Job re-split into {$job->total_chunks} chunks");
    }
}
