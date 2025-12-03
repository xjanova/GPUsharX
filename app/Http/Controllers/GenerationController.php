<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\GenerationJob;
use App\Models\RenderJob;
use App\Models\JobChunk;
use App\Services\GenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GenerationController extends Controller
{
    protected GenerationService $generationService;

    public function __construct(GenerationService $generationService)
    {
        $this->generationService = $generationService;
    }

    public function index()
    {
        $user = Auth::user();
        $featuredModels = AiModel::featured()->available()->get();
        $imageModels = AiModel::byType('image')->available()->get();
        $videoModels = AiModel::byType('video')->available()->get();

        $recentGenerations = $user ? GenerationJob::where('user_id', $user->id)
            ->with('aiModel')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get() : collect();

        $publicGallery = GenerationJob::public()
            ->completed()
            ->with(['user', 'aiModel'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // ดึงสถิติ Platform แบบ Real-time (ไม่ใช่ Demo)
        $platformStats = $this->generationService->getPlatformStats();

        return view('generate.index', compact(
            'featuredModels',
            'imageModels',
            'videoModels',
            'recentGenerations',
            'publicGallery',
            'platformStats'
        ));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'model_id' => 'required|exists:ai_models,id',
            'prompt' => 'required|string|max:2000',
            'negative_prompt' => 'nullable|string|max:1000',
            'width' => 'nullable|integer|min:256|max:2048',
            'height' => 'nullable|integer|min:256|max:2048',
            'steps' => 'nullable|integer|min:1|max:150',
            'cfg_scale' => 'nullable|numeric|min:1|max:30',
            'seed' => 'nullable|integer',
            'visibility' => 'nullable|in:private,public,unlisted',
        ]);

        $model = AiModel::findOrFail($validated['model_id']);
        $user = Auth::user();

        // Build params
        $params = [
            'width' => $validated['width'] ?? $model->default_params['width'] ?? 1024,
            'height' => $validated['height'] ?? $model->default_params['height'] ?? 1024,
            'steps' => $validated['steps'] ?? $model->default_params['steps'] ?? 30,
            'cfg_scale' => $validated['cfg_scale'] ?? $model->default_params['cfg_scale'] ?? 7.5,
            'seed' => $validated['seed'] ?? random_int(0, 2147483647),
            'negative_prompt' => $validated['negative_prompt'] ?? '',
        ];

        // ใช้ GenerationService สร้าง Job
        $result = $this->generationService->createGenerationJob(
            $user,
            $model,
            $validated['prompt'],
            $params,
            $validated['visibility'] ?? 'private'
        );

        if ($request->wantsJson()) {
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'message' => $result['message'],
                    'message_th' => $result['message_th'] ?? null,
                ], $result['error'] === 'no_workers' ? 503 : 400);
            }

            return response()->json([
                'success' => true,
                'job_id' => $result['job_id'],
                'message' => 'Generation job created',
                'credits_used' => $result['credits_used'],
                'estimated_time' => $result['estimated_time'],
                'workers_available' => $result['workers_available'],
            ]);
        }

        // HTML response
        if (!$result['success']) {
            $errorMessage = $result['message_th'] ?? $result['message'];
            return back()
                ->withInput()
                ->with('error', $errorMessage)
                ->with('no_workers', $result['error'] === 'no_workers');
        }

        return redirect()->route('generate.status', $result['job_id'])
            ->with('success', 'Generation started!');
    }

    public function status($jobId)
    {
        $job = GenerationJob::where('job_id', $jobId)
            ->with(['aiModel', 'user'])
            ->firstOrFail();

        // Check ownership or public
        if ($job->visibility !== 'public' && (!Auth::check() || $job->user_id !== Auth::id())) {
            abort(403);
        }

        return view('generate.status', compact('job'));
    }

    public function checkStatus($jobId)
    {
        $job = GenerationJob::where('job_id', $jobId)
            ->with(['processedByNode'])
            ->firstOrFail();

        // ดึง RenderJob เพื่อดู chunks
        $renderJob = RenderJob::where('job_id', $jobId)->first();

        // คำนวณ queue position
        $queuePosition = 0;
        if (in_array($job->status, ['queued', 'pending'])) {
            $queuePosition = GenerationJob::whereIn('status', ['queued', 'pending'])
                ->where('created_at', '<', $job->created_at)
                ->count() + 1;
        }

        // ดึง platform stats
        $platformStats = $this->generationService->getPlatformStats();

        // กำหนด stage และ stage message
        $stage = $this->getJobStage($job, $renderJob);

        return response()->json([
            'status' => $job->status,
            'progress' => $job->progress,
            'result_url' => $job->result_url,
            'result_thumbnail' => $job->result_thumbnail,
            'error_message' => $job->error_message,
            'processing_time_ms' => $job->processing_time_ms,
            // Stage info
            'stage' => $stage['stage'],
            'stage_name' => $stage['name'],
            'stage_name_th' => $stage['name_th'],
            'stage_icon' => $stage['icon'],
            'stage_progress' => $stage['progress'],
            // Queue info
            'queue_position' => $queuePosition,
            'queue_total' => $platformStats['queue']['total'],
            // Worker info
            'workers_online' => $platformStats['workers']['online'],
            'workers_available' => $platformStats['workers']['available'],
            'workers_working' => $platformStats['workers']['working'],
            'worker_name' => $job->processedByNode?->gpu_model,
            // Chunks info (if applicable)
            'total_chunks' => $renderJob?->total_chunks ?? 1,
            'completed_chunks' => $renderJob?->completed_chunks ?? 0,
            'assembly_status' => $renderJob?->assembly_status,
            // Time info
            'estimated_time' => $platformStats['performance']['avg_time_seconds'],
            'created_at' => $job->created_at->toIso8601String(),
            'started_at' => $job->started_at?->toIso8601String(),
        ]);
    }

    /**
     * กำหนด stage ของ job
     */
    protected function getJobStage(GenerationJob $job, ?RenderJob $renderJob): array
    {
        $stages = [
            'queued' => [
                'stage' => 1,
                'name' => 'Waiting in Queue',
                'name_th' => 'รอคิว',
                'icon' => 'clock',
                'progress' => 10,
            ],
            'matching' => [
                'stage' => 2,
                'name' => 'Matching Worker',
                'name_th' => 'กำลังจับคู่ Worker',
                'icon' => 'search',
                'progress' => 20,
            ],
            'processing' => [
                'stage' => 3,
                'name' => 'Processing',
                'name_th' => 'กำลังประมวลผล',
                'icon' => 'cog',
                'progress' => min(90, 30 + ($job->progress * 0.6)),
            ],
            'assembling' => [
                'stage' => 4,
                'name' => 'Assembling Result',
                'name_th' => 'กำลังรวมผลลัพธ์',
                'icon' => 'puzzle-piece',
                'progress' => 92,
            ],
            'uploading' => [
                'stage' => 5,
                'name' => 'Uploading',
                'name_th' => 'กำลังอัพโหลด',
                'icon' => 'cloud-upload-alt',
                'progress' => 96,
            ],
            'completed' => [
                'stage' => 6,
                'name' => 'Completed',
                'name_th' => 'เสร็จสิ้น',
                'icon' => 'check-circle',
                'progress' => 100,
            ],
            'failed' => [
                'stage' => 0,
                'name' => 'Failed',
                'name_th' => 'ล้มเหลว',
                'icon' => 'times-circle',
                'progress' => 0,
            ],
        ];

        // Determine current stage
        if ($job->status === 'completed') {
            return $stages['completed'];
        }

        if ($job->status === 'failed') {
            return $stages['failed'];
        }

        if ($job->status === 'queued' || $job->status === 'pending') {
            // Check if job has been assigned to a worker
            if ($renderJob && $renderJob->status === 'distributing') {
                return $stages['matching'];
            }
            return $stages['queued'];
        }

        if ($job->status === 'processing') {
            // Check assembly status
            if ($renderJob) {
                if ($renderJob->assembly_status === 'assembling') {
                    return $stages['assembling'];
                }
                if ($renderJob->assembly_status === 'uploading' || $job->progress >= 95) {
                    return $stages['uploading'];
                }
            }
            return $stages['processing'];
        }

        return $stages['queued'];
    }

    public function gallery()
    {
        $gallery = GenerationJob::public()
            ->completed()
            ->with(['user', 'aiModel'])
            ->orderBy('created_at', 'desc')
            ->paginate(24);

        return view('generate.gallery', compact('gallery'));
    }

    public function myGenerations()
    {
        $generations = GenerationJob::where('user_id', Auth::id())
            ->with('aiModel')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('generate.my-generations', compact('generations'));
    }

    protected function calculateCredits(AiModel $model, array $params): float
    {
        $baseCredits = $model->type === 'image' ? 1 : 5;

        // Scale by resolution
        $pixels = ($params['width'] ?? 512) * ($params['height'] ?? 512);
        $resolutionMultiplier = $pixels / (512 * 512);

        // Scale by steps
        $stepsMultiplier = ($params['steps'] ?? 20) / 20;

        return round($baseCredits * $resolutionMultiplier * $stepsMultiplier, 2);
    }
}
