<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'name',
        'huggingface_id',
        'type',
        'category',
        'description',
        'thumbnail',
        'vram_required_mb',
        'size_mb',
        'default_params',
        'supported_params',
        'status',
        'download_progress',
        'local_path',
        'popularity',
        'avg_generation_time',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'default_params' => 'array',
        'supported_params' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function generationJobs(): HasMany
    {
        return $this->hasMany(GenerationJob::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ModelDownload::class);
    }

    public function scopeInstalled($query)
    {
        return $query->where('status', 'installed');
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['available', 'installed']);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getVramRequiredGbAttribute(): float
    {
        return round($this->vram_required_mb / 1024, 1);
    }

    public function getSizeGbAttribute(): float
    {
        return round($this->size_mb / 1024, 2);
    }

    public static function getRecommendedModels(): array
    {
        return [
            // Image Generation
            [
                'model_id' => 'sdxl-base',
                'name' => 'Stable Diffusion XL Base',
                'huggingface_id' => 'stabilityai/stable-diffusion-xl-base-1.0',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'High-quality image generation with improved composition and face generation.',
                'vram_required_mb' => 8192,
                'size_mb' => 6800,
                'is_featured' => true,
                'default_params' => [
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 30,
                    'cfg_scale' => 7.5,
                ],
            ],
            [
                'model_id' => 'sd-1.5',
                'name' => 'Stable Diffusion 1.5',
                'huggingface_id' => 'runwayml/stable-diffusion-v1-5',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'Classic SD 1.5 - fast and efficient for most use cases.',
                'vram_required_mb' => 4096,
                'size_mb' => 4200,
                'default_params' => [
                    'width' => 512,
                    'height' => 512,
                    'steps' => 25,
                    'cfg_scale' => 7.0,
                ],
            ],
            [
                'model_id' => 'flux-schnell',
                'name' => 'FLUX.1 Schnell',
                'huggingface_id' => 'black-forest-labs/FLUX.1-schnell',
                'type' => 'image',
                'category' => 'flux',
                'description' => 'Ultra-fast FLUX model - 4 steps only, Apache 2.0 license.',
                'vram_required_mb' => 12288,
                'size_mb' => 23000,
                'is_featured' => true,
                'default_params' => [
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 4,
                    'guidance_scale' => 0,
                ],
            ],
            [
                'model_id' => 'flux-dev',
                'name' => 'FLUX.1 Dev',
                'huggingface_id' => 'black-forest-labs/FLUX.1-dev',
                'type' => 'image',
                'category' => 'flux',
                'description' => 'High-quality FLUX model for best results.',
                'vram_required_mb' => 16384,
                'size_mb' => 23000,
                'default_params' => [
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 20,
                    'guidance_scale' => 3.5,
                ],
            ],
            // Video Generation
            [
                'model_id' => 'animatediff-lightning',
                'name' => 'AnimateDiff Lightning',
                'huggingface_id' => 'ByteDance/AnimateDiff-Lightning',
                'type' => 'video',
                'category' => 'animatediff',
                'description' => 'Fast video generation - 4 steps only.',
                'vram_required_mb' => 12288,
                'size_mb' => 8500,
                'is_featured' => true,
                'default_params' => [
                    'width' => 512,
                    'height' => 512,
                    'frames' => 16,
                    'steps' => 4,
                ],
            ],
            [
                'model_id' => 'cogvideox-5b',
                'name' => 'CogVideoX 5B',
                'huggingface_id' => 'THUDM/CogVideoX-5b',
                'type' => 'video',
                'category' => 'cogvideo',
                'description' => 'Text-to-video generation with high quality.',
                'vram_required_mb' => 24576,
                'size_mb' => 20000,
                'default_params' => [
                    'width' => 720,
                    'height' => 480,
                    'frames' => 49,
                    'steps' => 50,
                ],
            ],
            [
                'model_id' => 'stable-video-diffusion',
                'name' => 'Stable Video Diffusion',
                'huggingface_id' => 'stabilityai/stable-video-diffusion-img2vid-xt',
                'type' => 'video',
                'category' => 'other',
                'description' => 'Image-to-video generation from Stability AI.',
                'vram_required_mb' => 16384,
                'size_mb' => 9500,
                'is_featured' => true,
                'default_params' => [
                    'frames' => 25,
                    'fps' => 7,
                    'motion_bucket_id' => 127,
                ],
            ],
            // Realtime/Fast
            [
                'model_id' => 'sdxl-turbo',
                'name' => 'SDXL Turbo',
                'huggingface_id' => 'stabilityai/sdxl-turbo',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'Real-time image generation - 1-4 steps.',
                'vram_required_mb' => 8192,
                'size_mb' => 6800,
                'default_params' => [
                    'width' => 512,
                    'height' => 512,
                    'steps' => 1,
                    'cfg_scale' => 0,
                ],
            ],
            [
                'model_id' => 'lcm-dreamshaper',
                'name' => 'LCM DreamShaper',
                'huggingface_id' => 'SimianLuo/LCM_Dreamshaper_v7',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'Latent Consistency Model - 4-8 steps.',
                'vram_required_mb' => 4096,
                'size_mb' => 4200,
                'default_params' => [
                    'width' => 512,
                    'height' => 512,
                    'steps' => 4,
                    'cfg_scale' => 1.0,
                ],
            ],
        ];
    }
}
