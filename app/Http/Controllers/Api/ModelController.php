<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\GpuNode;
use App\Services\ModelDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function __construct(
        protected ModelDownloadService $modelService
    ) {}

    /**
     * Get list of available models for a node
     */
    public function getAvailableModels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $models = $this->modelService->getAvailableModels($node);

        return response()->json([
            'success' => true,
            'data' => [
                'models' => $models,
                'node_vram_mb' => $node->gpu_vram_mb,
                'recommended' => $this->modelService->getRecommendedModel($node),
            ],
        ]);
    }

    /**
     * Get download instructions for a model
     */
    public function getDownloadInstructions(Request $request, string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();

        $instructions = $this->modelService->getDownloadInstructions($model);

        return response()->json([
            'success' => true,
            'data' => $instructions,
        ]);
    }

    /**
     * Register that a model has been installed on a node
     */
    public function registerInstallation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'model_id' => 'required|string|exists:ai_models,model_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $success = $this->modelService->registerModelInstallation($node, $validated['model_id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Model installation registered' : 'Failed to register installation',
            'data' => [
                'installed_models' => $this->modelService->getInstalledModels($node),
            ],
        ]);
    }

    /**
     * Unregister a model from a node
     */
    public function unregisterInstallation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'model_id' => 'required|string',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $success = $this->modelService->unregisterModelInstallation($node, $validated['model_id']);

        return response()->json([
            'success' => $success,
            'message' => 'Model unregistered',
            'data' => [
                'installed_models' => $this->modelService->getInstalledModels($node),
            ],
        ]);
    }

    /**
     * Get installed models for a node
     */
    public function getInstalledModels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'installed_models' => $this->modelService->getInstalledModels($node),
            ],
        ]);
    }

    /**
     * Get all models (for public listing)
     */
    public function getAllModels(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $category = $request->query('category');

        $query = AiModel::where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        if ($category) {
            $query->where('category', $category);
        }

        $models = $query->orderBy('is_featured', 'desc')
            ->orderBy('popularity', 'desc')
            ->get()
            ->map(function ($model) {
                return [
                    'model_id' => $model->model_id,
                    'name' => $model->name,
                    'huggingface_id' => $model->huggingface_id,
                    'type' => $model->type,
                    'category' => $model->category,
                    'description' => $model->description,
                    'thumbnail' => $model->thumbnail,
                    'vram_required_mb' => $model->vram_required_mb,
                    'vram_required_gb' => $model->vram_required_gb,
                    'size_mb' => $model->size_mb,
                    'size_gb' => $model->size_gb,
                    'is_featured' => $model->is_featured,
                    'popularity' => $model->popularity,
                    'default_params' => $model->default_params,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'models' => $models,
                'types' => ['image', 'video', 'audio', 'text', '3d'],
                'categories' => ['stable_diffusion', 'flux', 'animatediff', 'cogvideo', 'other'],
            ],
        ]);
    }

    /**
     * Get model details
     */
    public function getModel(string $modelId): JsonResponse
    {
        $model = AiModel::where('model_id', $modelId)->firstOrFail();

        // Get HuggingFace info
        $hfInfo = $this->modelService->getHuggingFaceModelInfo($model->huggingface_id);

        return response()->json([
            'success' => true,
            'data' => [
                'model' => [
                    'model_id' => $model->model_id,
                    'name' => $model->name,
                    'huggingface_id' => $model->huggingface_id,
                    'type' => $model->type,
                    'category' => $model->category,
                    'description' => $model->description,
                    'thumbnail' => $model->thumbnail,
                    'vram_required_mb' => $model->vram_required_mb,
                    'size_mb' => $model->size_mb,
                    'is_featured' => $model->is_featured,
                    'popularity' => $model->popularity,
                    'default_params' => $model->default_params,
                    'supported_params' => $model->supported_params,
                ],
                'huggingface_info' => $hfInfo,
                'download_instructions' => $this->modelService->getDownloadInstructions($model),
            ],
        ]);
    }

    /**
     * Seed recommended models (admin only)
     */
    public function seedModels(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $created = $this->modelService->seedRecommendedModels();

        return response()->json([
            'success' => true,
            'message' => "Created {$created} new models",
            'data' => [
                'created_count' => $created,
                'total_models' => AiModel::count(),
            ],
        ]);
    }
}
