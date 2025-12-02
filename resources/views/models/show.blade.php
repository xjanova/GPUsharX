@extends('layouts.app')

@section('title', $model->name . ' - AI Model')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('models') }}" class="text-gray-400 hover:text-white transition">Models</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white">{{ $model->name }}</span>
        </nav>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Model Header -->
            <div class="glass-card rounded-2xl p-6">
                <div class="flex items-start gap-6">
                    @if($model->thumbnail)
                    <div class="w-32 h-32 rounded-xl overflow-hidden flex-shrink-0 bg-gray-800">
                        <img src="{{ $model->thumbnail }}" alt="{{ $model->name }}" class="w-full h-full object-cover">
                    </div>
                    @else
                    <div class="w-32 h-32 rounded-xl flex-shrink-0 bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif

                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-2xl font-bold text-white">{{ $model->name }}</h1>
                            @if($model->is_featured)
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-400">Featured</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $model->type === 'image' ? 'bg-purple-500/20 text-purple-400' : '' }}
                                {{ $model->type === 'video' ? 'bg-pink-500/20 text-pink-400' : '' }}">
                                {{ ucfirst($model->type) }}
                            </span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                                {{ ucfirst($model->category) }}
                            </span>
                        </div>

                        <p class="text-gray-400">{{ $model->description }}</p>
                    </div>
                </div>
            </div>

            <!-- Installation Guide -->
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Installation Guide
                </h2>

                <div class="space-y-6">
                    <!-- Step 1 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">1</div>
                        <div class="flex-1">
                            <h3 class="text-white font-medium mb-2">Install Python Dependencies</h3>
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu121
pip install diffusers transformers accelerate safetensors</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">2</div>
                        <div class="flex-1">
                            <h3 class="text-white font-medium mb-2">Download Model from HuggingFace</h3>
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>from huggingface_hub import snapshot_download

model_id = "{{ $model->huggingface_id }}"
local_dir = "./models/{{ $model->model_id }}"

snapshot_download(
    repo_id=model_id,
    local_dir=local_dir,
    local_dir_use_symlinks=False
)</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">3</div>
                        <div class="flex-1">
                            <h3 class="text-white font-medium mb-2">Load and Run Model</h3>
                            @if($model->category === 'flux')
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>from diffusers import FluxPipeline
import torch

pipe = FluxPipeline.from_pretrained(
    "{{ $model->huggingface_id }}",
    torch_dtype=torch.bfloat16
)
pipe.to("cuda")

prompt = "A beautiful sunset over mountains"
image = pipe(prompt, num_inference_steps=4).images[0]
image.save("output.png")</pre>
                            </div>
                            @elseif($model->category === 'stable_diffusion')
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>from diffusers import StableDiffusionXLPipeline
import torch

pipe = StableDiffusionXLPipeline.from_pretrained(
    "{{ $model->huggingface_id }}",
    torch_dtype=torch.float16,
    use_safetensors=True,
    variant="fp16"
)
pipe.to("cuda")

prompt = "A beautiful sunset over mountains"
image = pipe(prompt).images[0]
image.save("output.png")</pre>
                            </div>
                            @elseif($model->category === 'animatediff')
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>from diffusers import AnimateDiffPipeline, DDIMScheduler
import torch

pipe = AnimateDiffPipeline.from_pretrained(
    "{{ $model->huggingface_id }}",
    torch_dtype=torch.float16
)
pipe.to("cuda")

prompt = "A cute cat running in the garden"
frames = pipe(prompt, num_frames=16).frames[0]
# Save as GIF
frames[0].save("output.gif", save_all=True,
    append_images=frames[1:], duration=125, loop=0)</pre>
                            </div>
                            @elseif($model->category === 'cogvideo')
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>from diffusers import CogVideoXPipeline
import torch

pipe = CogVideoXPipeline.from_pretrained(
    "{{ $model->huggingface_id }}",
    torch_dtype=torch.bfloat16
)
pipe.to("cuda")
pipe.enable_model_cpu_offload()

prompt = "A majestic eagle soaring through clouds"
video = pipe(prompt, num_frames=49).frames[0]
# Export using export_to_video helper</pre>
                            </div>
                            @else
                            <div class="bg-black/50 rounded-lg p-4 font-mono text-sm text-gray-300 overflow-x-auto">
                                <pre>from diffusers import DiffusionPipeline
import torch

pipe = DiffusionPipeline.from_pretrained(
    "{{ $model->huggingface_id }}",
    torch_dtype=torch.float16
)
pipe.to("cuda")

output = pipe("your prompt here")
output.images[0].save("output.png")</pre>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- HuggingFace Link -->
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">HuggingFace Repository</h3>
                <a href="https://huggingface.co/{{ $model->huggingface_id }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-3 px-4 py-3 rounded-xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-400 hover:bg-yellow-500/20 transition">
                    <svg class="w-6 h-6" viewBox="0 0 95 88" fill="currentColor">
                        <path d="M47.21 0c-25.32 0-45.87 20.17-45.87 45.05 0 20.01 13.41 36.91 31.9 42.69 2.34.43 3.21-.98 3.21-2.17 0-1.07-.04-4.62-.06-8.37-12.98 2.77-15.72-5.48-15.72-5.48-2.12-5.31-5.18-6.72-5.18-6.72-4.23-2.85.32-2.79.32-2.79 4.68.33 7.14 4.74 7.14 4.74 4.16 7.03 10.91 5 13.57 3.82.42-2.97 1.63-5 2.96-6.15-10.36-1.16-21.26-5.11-21.26-22.73 0-5.02 1.82-9.13 4.81-12.35-.48-1.16-2.08-5.84.46-12.17 0 0 3.92-1.24 12.84 4.71 3.73-1.02 7.72-1.53 11.69-1.55 3.97.02 7.96.53 11.69 1.55 8.92-5.95 12.83-4.71 12.83-4.71 2.55 6.33.95 11.01.47 12.17 2.99 3.22 4.8 7.33 4.8 12.35 0 17.68-10.92 21.55-21.32 22.69 1.68 1.42 3.17 4.23 3.17 8.53 0 6.16-.06 11.13-.06 12.64 0 1.21.85 2.62 3.22 2.18 18.48-5.8 31.86-22.69 31.86-42.69C93.08 20.17 72.53 0 47.21 0z"/>
                    </svg>
                    <span>{{ $model->huggingface_id }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Specs -->
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Specifications</h3>
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-gray-400">VRAM Required</dt>
                        <dd class="text-white font-medium">{{ number_format($model->vram_required_mb / 1024, 1) }} GB</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">Model Size</dt>
                        <dd class="text-white font-medium">{{ number_format($model->size_mb / 1024, 1) }} GB</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">Avg Generation Time</dt>
                        <dd class="text-white font-medium">{{ $model->avg_generation_time ? $model->avg_generation_time . 's' : 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">Popularity</dt>
                        <dd class="text-white font-medium">{{ number_format($model->popularity) }} uses</dd>
                    </div>
                </dl>
            </div>

            <!-- Default Parameters -->
            @if($model->default_params)
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Default Parameters</h3>
                <dl class="space-y-3">
                    @foreach($model->default_params as $key => $value)
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-400">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                        <dd class="text-white font-mono">{{ $value }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="space-y-3">
                <a href="{{ route('generate') }}?model={{ $model->id }}" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Generate Now
                </a>
                <a href="{{ route('models') }}" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    Back to Models
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
