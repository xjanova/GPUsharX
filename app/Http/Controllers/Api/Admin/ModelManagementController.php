<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\GenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModelManagementController extends Controller
{
    /**
     * List all models with stats
     */
    public function index(Request $request): JsonResponse
    {
        $query = AiModel::query();

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Add usage stats
        $models = $query->orderBy('is_featured', 'desc')
            ->orderBy('popularity', 'desc')
            ->get()
            ->map(function ($model) {
                $stats = GenerationJob::where('ai_model_id', $model->id)
                    ->selectRaw('COUNT(*) as total_uses, SUM(credits_used) as total_credits')
                    ->first();

                return [
                    'id' => $model->id,
                    'model_id' => $model->model_id,
                    'name' => $model->name,
                    'huggingface_id' => $model->huggingface_id,
                    'type' => $model->type,
                    'category' => $model->category,
                    'description' => $model->description,
                    'thumbnail' => $model->thumbnail,
                    'vram_required_mb' => $model->vram_required_mb,
                    'size_mb' => $model->size_mb,
                    'is_active' => $model->is_active,
                    'is_featured' => $model->is_featured,
                    'popularity' => $model->popularity,
                    'default_params' => $model->default_params,
                    'supported_params' => $model->supported_params,
                    'created_at' => $model->created_at,
                    'stats' => [
                        'total_uses' => $stats->total_uses ?? 0,
                        'total_credits' => round($stats->total_credits ?? 0, 2),
                        'revenue' => round(($stats->total_credits ?? 0) * 0.10, 2),
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'models' => $models,
                'total' => $models->count(),
                'filters' => [
                    'types' => ['image', 'video', 'audio', 'text', '3d'],
                    'categories' => ['stable_diffusion', 'flux', 'animatediff', 'cogvideo', 'other'],
                ],
            ],
        ]);
    }

    /**
     * Get single model details
     */
    public function show(string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();

        // Usage history (last 30 days)
        $usageHistory = GenerationJob::where('ai_model_id', $model->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as uses, SUM(credits_used) as credits')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Top users of this model
        $topUsers = GenerationJob::where('ai_model_id', $model->id)
            ->selectRaw('user_id, COUNT(*) as uses, SUM(credits_used) as credits')
            ->groupBy('user_id')
            ->orderByDesc('uses')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'model' => $model,
                'usage_history' => $usageHistory,
                'top_users' => $topUsers,
            ],
        ]);
    }

    /**
     * Create new model
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'huggingface_id' => 'required|string|max:255|unique:ai_models,huggingface_id',
            'type' => 'required|in:image,video,audio,text,3d',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'vram_required_mb' => 'required|integer|min:1024',
            'size_mb' => 'nullable|integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'default_params' => 'nullable|array',
            'supported_params' => 'nullable|array',
        ]);

        // Generate model_id from name
        $validated['model_id'] = Str::slug($validated['name']);

        // Check if model_id already exists
        $counter = 1;
        $baseModelId = $validated['model_id'];
        while (AiModel::where('model_id', $validated['model_id'])->exists()) {
            $validated['model_id'] = $baseModelId . '-' . $counter;
            $counter++;
        }

        $validated['status'] = 'available';
        $validated['popularity'] = 0;

        if (isset($validated['default_params'])) {
            $validated['default_params'] = json_encode($validated['default_params']);
        }
        if (isset($validated['supported_params'])) {
            $validated['supported_params'] = json_encode($validated['supported_params']);
        }

        $model = AiModel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Model created successfully',
            'data' => $model,
        ], 201);
    }

    /**
     * Update model
     */
    public function update(Request $request, string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'vram_required_mb' => 'integer|min:1024',
            'size_mb' => 'nullable|integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'default_params' => 'nullable|array',
            'supported_params' => 'nullable|array',
        ]);

        if (isset($validated['default_params'])) {
            $validated['default_params'] = json_encode($validated['default_params']);
        }
        if (isset($validated['supported_params'])) {
            $validated['supported_params'] = json_encode($validated['supported_params']);
        }

        $model->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Model updated successfully',
            'data' => $model->fresh(),
        ]);
    }

    /**
     * Toggle model active status
     */
    public function toggleActive(string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();
        $model->update(['is_active' => !$model->is_active]);

        return response()->json([
            'success' => true,
            'message' => $model->is_active ? 'Model activated' : 'Model deactivated',
            'data' => ['is_active' => $model->is_active],
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();
        $model->update(['is_featured' => !$model->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $model->is_featured ? 'Model featured' : 'Model unfeatured',
            'data' => ['is_featured' => $model->is_featured],
        ]);
    }

    /**
     * Delete model
     */
    public function destroy(string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();

        // Check if model has been used
        $usageCount = GenerationJob::where('ai_model_id', $model->id)->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete model with {$usageCount} existing jobs. Deactivate instead.",
            ], 422);
        }

        $model->delete();

        return response()->json([
            'success' => true,
            'message' => 'Model deleted successfully',
        ]);
    }

    /**
     * Bulk update models
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_ids' => 'required|array',
            'model_ids.*' => 'string|exists:ai_models,model_id',
            'action' => 'required|in:activate,deactivate,feature,unfeature',
        ]);

        $updateData = match ($validated['action']) {
            'activate' => ['is_active' => true],
            'deactivate' => ['is_active' => false],
            'feature' => ['is_featured' => true],
            'unfeature' => ['is_featured' => false],
        };

        $affected = AiModel::whereIn('model_id', $validated['model_ids'])
            ->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "{$affected} models updated",
            'data' => ['affected' => $affected],
        ]);
    }
}
