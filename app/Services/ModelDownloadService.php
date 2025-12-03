<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\GpuNode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ModelDownloadService - Manages AI model downloads and installation
 *
 * Handles model download coordination between server and worker nodes
 */
class ModelDownloadService
{
    /**
     * HuggingFace API base URL
     */
    protected string $hfApiUrl = 'https://huggingface.co/api/models';

    /**
     * Get available models for a node based on its VRAM
     */
    public function getAvailableModels(GpuNode $node): array
    {
        $vramMb = $node->gpu_vram_mb ?? 8192;

        return AiModel::where('is_active', true)
            ->where('vram_required_mb', '<=', $vramMb)
            ->orderBy('is_featured', 'desc')
            ->orderBy('popularity', 'desc')
            ->get()
            ->map(function ($model) use ($node) {
                return [
                    'model_id' => $model->model_id,
                    'name' => $model->name,
                    'huggingface_id' => $model->huggingface_id,
                    'type' => $model->type,
                    'category' => $model->category,
                    'description' => $model->description,
                    'vram_required_mb' => $model->vram_required_mb,
                    'size_mb' => $model->size_mb,
                    'is_featured' => $model->is_featured,
                    'default_params' => $model->default_params,
                    'is_installed' => $this->isModelInstalledOnNode($model, $node),
                    'download_url' => $this->getModelDownloadUrl($model),
                ];
            })
            ->toArray();
    }

    /**
     * Get model info from HuggingFace
     */
    public function getHuggingFaceModelInfo(string $huggingfaceId): ?array
    {
        $cacheKey = "hf_model_info:" . md5($huggingfaceId);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($huggingfaceId) {
            try {
                $response = Http::timeout(30)->get("{$this->hfApiUrl}/{$huggingfaceId}");

                if ($response->successful()) {
                    $data = $response->json();

                    // Get file sizes
                    $filesResponse = Http::timeout(30)->get("{$this->hfApiUrl}/{$huggingfaceId}/tree/main");
                    $files = $filesResponse->successful() ? $filesResponse->json() : [];

                    $totalSize = 0;
                    $modelFiles = [];

                    foreach ($files as $file) {
                        if (isset($file['size'])) {
                            $totalSize += $file['size'];
                        }

                        // Identify important model files
                        $filename = $file['path'] ?? '';
                        if (preg_match('/\.(safetensors|bin|ckpt|pt|pth)$/i', $filename)) {
                            $modelFiles[] = [
                                'path' => $filename,
                                'size' => $file['size'] ?? 0,
                                'url' => "https://huggingface.co/{$huggingfaceId}/resolve/main/{$filename}",
                            ];
                        }
                    }

                    return [
                        'id' => $huggingfaceId,
                        'name' => $data['modelId'] ?? $huggingfaceId,
                        'author' => $data['author'] ?? 'Unknown',
                        'downloads' => $data['downloads'] ?? 0,
                        'likes' => $data['likes'] ?? 0,
                        'tags' => $data['tags'] ?? [],
                        'pipeline_tag' => $data['pipeline_tag'] ?? null,
                        'total_size_bytes' => $totalSize,
                        'total_size_mb' => round($totalSize / (1024 * 1024)),
                        'model_files' => $modelFiles,
                        'library_name' => $data['library_name'] ?? null,
                    ];
                }

                return null;
            } catch (\Exception $e) {
                Log::warning("Failed to get HuggingFace model info", [
                    'huggingface_id' => $huggingfaceId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Get download instructions for a model
     */
    public function getDownloadInstructions(AiModel $model): array
    {
        $hfInfo = $this->getHuggingFaceModelInfo($model->huggingface_id);

        $files = [];
        if ($hfInfo && isset($hfInfo['model_files'])) {
            $files = $hfInfo['model_files'];
        }

        // If no files found, construct default URLs based on model type
        if (empty($files)) {
            $files = $this->getDefaultModelFiles($model);
        }

        return [
            'model_id' => $model->model_id,
            'huggingface_id' => $model->huggingface_id,
            'category' => $model->category,
            'install_path' => $this->getInstallPath($model),
            'files' => $files,
            'total_size_mb' => $model->size_mb,
            'post_download_steps' => $this->getPostDownloadSteps($model),
            'verification' => [
                'check_files' => $this->getVerificationFiles($model),
            ],
        ];
    }

    /**
     * Get default model files based on model category
     */
    protected function getDefaultModelFiles(AiModel $model): array
    {
        $baseUrl = "https://huggingface.co/{$model->huggingface_id}/resolve/main";

        return match ($model->category) {
            'stable_diffusion' => [
                ['path' => 'model_index.json', 'url' => "{$baseUrl}/model_index.json", 'required' => true],
                ['path' => 'unet/diffusion_pytorch_model.safetensors', 'url' => "{$baseUrl}/unet/diffusion_pytorch_model.safetensors", 'required' => true],
                ['path' => 'vae/diffusion_pytorch_model.safetensors', 'url' => "{$baseUrl}/vae/diffusion_pytorch_model.safetensors", 'required' => true],
                ['path' => 'text_encoder/model.safetensors', 'url' => "{$baseUrl}/text_encoder/model.safetensors", 'required' => true],
                ['path' => 'tokenizer/vocab.json', 'url' => "{$baseUrl}/tokenizer/vocab.json", 'required' => true],
                ['path' => 'scheduler/scheduler_config.json', 'url' => "{$baseUrl}/scheduler/scheduler_config.json", 'required' => true],
            ],
            'flux' => [
                ['path' => 'flux1-schnell.safetensors', 'url' => "{$baseUrl}/flux1-schnell.safetensors", 'required' => true],
                ['path' => 'ae.safetensors', 'url' => "{$baseUrl}/ae.safetensors", 'required' => true],
            ],
            'animatediff' => [
                ['path' => 'mm_sd_v15_v2.ckpt', 'url' => "{$baseUrl}/mm_sd_v15_v2.ckpt", 'required' => true],
            ],
            default => [
                ['path' => 'model.safetensors', 'url' => "{$baseUrl}/model.safetensors", 'required' => true],
            ],
        };
    }

    /**
     * Get install path for model
     */
    protected function getInstallPath(AiModel $model): string
    {
        return match ($model->category) {
            'stable_diffusion' => "models/stable-diffusion/{$model->model_id}",
            'flux' => "models/flux/{$model->model_id}",
            'animatediff' => "models/animatediff/{$model->model_id}",
            'cogvideo' => "models/cogvideo/{$model->model_id}",
            default => "models/other/{$model->model_id}",
        };
    }

    /**
     * Get post-download steps
     */
    protected function getPostDownloadSteps(AiModel $model): array
    {
        $steps = [
            'verify_files' => true,
            'create_config' => true,
        ];

        if ($model->category === 'stable_diffusion') {
            $steps['convert_to_diffusers'] = false; // Already in diffusers format
        }

        return $steps;
    }

    /**
     * Get files to verify after download
     */
    protected function getVerificationFiles(AiModel $model): array
    {
        return match ($model->category) {
            'stable_diffusion' => ['model_index.json', 'unet/config.json'],
            'flux' => ['flux1-schnell.safetensors'],
            'animatediff' => ['mm_sd_v15_v2.ckpt'],
            default => ['model.safetensors'],
        };
    }

    /**
     * Check if model is installed on a node
     */
    public function isModelInstalledOnNode(AiModel $model, GpuNode $node): bool
    {
        $installedModels = $node->installed_models ?? [];
        return in_array($model->model_id, $installedModels);
    }

    /**
     * Get model download URL (for direct download)
     */
    protected function getModelDownloadUrl(AiModel $model): string
    {
        return "https://huggingface.co/{$model->huggingface_id}";
    }

    /**
     * Register that a node has installed a model
     */
    public function registerModelInstallation(GpuNode $node, string $modelId): bool
    {
        $model = AiModel::where('model_id', $modelId)->first();
        if (!$model) {
            return false;
        }

        $installedModels = $node->installed_models ?? [];
        if (!in_array($modelId, $installedModels)) {
            $installedModels[] = $modelId;
            $node->update(['installed_models' => $installedModels]);
        }

        // Increment model popularity
        $model->increment('popularity');

        Log::info("Model installed on node", [
            'node_id' => $node->node_id,
            'model_id' => $modelId,
        ]);

        return true;
    }

    /**
     * Unregister model from node
     */
    public function unregisterModelInstallation(GpuNode $node, string $modelId): bool
    {
        $installedModels = $node->installed_models ?? [];
        $installedModels = array_values(array_diff($installedModels, [$modelId]));
        $node->update(['installed_models' => $installedModels]);

        return true;
    }

    /**
     * Get models installed on a node
     */
    public function getInstalledModels(GpuNode $node): array
    {
        $installedModelIds = $node->installed_models ?? [];

        return AiModel::whereIn('model_id', $installedModelIds)
            ->get()
            ->map(function ($model) {
                return [
                    'model_id' => $model->model_id,
                    'name' => $model->name,
                    'type' => $model->type,
                    'category' => $model->category,
                    'size_mb' => $model->size_mb,
                ];
            })
            ->toArray();
    }

    /**
     * Get recommended model for quick start
     */
    public function getRecommendedModel(GpuNode $node): ?array
    {
        $vramMb = $node->gpu_vram_mb ?? 8192;

        // Find a featured, installed-friendly model that fits VRAM
        $model = AiModel::where('is_active', true)
            ->where('is_featured', true)
            ->where('vram_required_mb', '<=', $vramMb)
            ->orderBy('size_mb', 'asc') // Prefer smaller models for quick start
            ->first();

        if (!$model) {
            // Fall back to any model that fits
            $model = AiModel::where('is_active', true)
                ->where('vram_required_mb', '<=', $vramMb)
                ->orderBy('size_mb', 'asc')
                ->first();
        }

        if (!$model) {
            return null;
        }

        return [
            'model_id' => $model->model_id,
            'name' => $model->name,
            'huggingface_id' => $model->huggingface_id,
            'type' => $model->type,
            'category' => $model->category,
            'size_mb' => $model->size_mb,
            'reason' => $model->is_featured ? 'Featured model suitable for your GPU' : 'Smallest model suitable for your GPU',
        ];
    }

    /**
     * Seed recommended models to database
     */
    public function seedRecommendedModels(): int
    {
        $models = AiModel::getRecommendedModels();
        $created = 0;

        foreach ($models as $modelData) {
            $existing = AiModel::where('model_id', $modelData['model_id'])->first();
            if (!$existing) {
                AiModel::create(array_merge($modelData, [
                    'status' => 'available',
                    'is_active' => true,
                ]));
                $created++;
            }
        }

        return $created;
    }
}
