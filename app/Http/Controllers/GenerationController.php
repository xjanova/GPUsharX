<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\GenerationJob;
use App\Models\RenderJob;
use App\Models\JobChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GenerationController extends Controller
{
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

        return view('generate.index', compact(
            'featuredModels',
            'imageModels',
            'videoModels',
            'recentGenerations',
            'publicGallery'
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
        $params = array_merge($model->default_params ?? [], [
            'width' => $validated['width'] ?? 1024,
            'height' => $validated['height'] ?? 1024,
            'steps' => $validated['steps'] ?? 30,
            'cfg_scale' => $validated['cfg_scale'] ?? 7.5,
            'seed' => $validated['seed'] ?? random_int(0, 2147483647),
        ]);

        // Calculate credits
        $credits = $this->calculateCredits($model, $params);

        // Create generation job
        $genJob = GenerationJob::create([
            'user_id' => $user->id,
            'ai_model_id' => $model->id,
            'type' => $model->type,
            'prompt' => $validated['prompt'],
            'negative_prompt' => $validated['negative_prompt'] ?? '',
            'params' => $params,
            'credits_used' => $credits,
            'visibility' => $validated['visibility'] ?? 'private',
        ]);

        // Create render job for distribution
        $renderJob = RenderJob::create([
            'job_id' => $genJob->job_id,
            'title' => Str::limit($validated['prompt'], 50),
            'description' => $validated['prompt'],
            'type' => $model->type,
            'priority' => 'normal',
            'status' => 'queued',
            'estimated_credits' => $credits,
            'required_vram_mb' => $model->vram_required_mb,
            'total_chunks' => 1,
            'job_params' => [
                'generation_job_id' => $genJob->id,
                'model_id' => $model->huggingface_id,
                'prompt' => $validated['prompt'],
                'negative_prompt' => $validated['negative_prompt'] ?? '',
                'params' => $params,
            ],
            'created_by' => $user->id,
        ]);

        // Create single chunk
        JobChunk::create([
            'chunk_id' => 'CHUNK-' . strtoupper(Str::random(10)),
            'render_job_id' => $renderJob->id,
            'chunk_index' => 0,
            'status' => 'pending',
            'chunk_params' => $renderJob->job_params,
            'credits_earned' => $credits,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'job_id' => $genJob->job_id,
                'message' => 'Generation job created',
            ]);
        }

        return redirect()->route('generate.status', $genJob->job_id)
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
        $job = GenerationJob::where('job_id', $jobId)->firstOrFail();

        return response()->json([
            'status' => $job->status,
            'progress' => $job->progress,
            'result_url' => $job->result_url,
            'result_thumbnail' => $job->result_thumbnail,
            'error_message' => $job->error_message,
        ]);
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
