@extends('layouts.app')

@section('title', 'AI Models')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="gradient-text">AI Models Library</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Explore and use the best open-source AI models from HuggingFace.
            </p>
        </div>

        <!-- Featured Models -->
        @if($featuredModels->count() > 0)
        <div class="mb-16">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-star text-yellow-400 mr-3"></i>
                Featured Models
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($featuredModels as $model)
                <div class="glass-card rounded-2xl overflow-hidden card-hover group">
                    <div class="h-40 bg-gradient-to-br from-purple-600/30 to-pink-600/30 relative">
                        @if($model->thumbnail)
                        <img src="{{ $model->thumbnail }}" alt="" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-cube text-6xl text-purple-400/50"></i>
                        </div>
                        @endif
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $model->type === 'image' ? 'bg-blue-500/80' : 'bg-pink-500/80' }}">
                                {{ ucfirst($model->type) }}
                            </span>
                        </div>
                        @if($model->status === 'installed')
                        <div class="absolute top-3 right-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-500/80">
                                <i class="fas fa-check mr-1"></i>Ready
                            </span>
                        </div>
                        @endif
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-2">{{ $model->name }}</h3>
                        <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $model->description }}</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">
                                <i class="fas fa-microchip mr-1"></i>{{ $model->vram_required_gb }}GB VRAM
                            </span>
                            <span class="text-gray-500">
                                <i class="fas fa-hdd mr-1"></i>{{ $model->size_gb }}GB
                            </span>
                        </div>
                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('generate') }}?model={{ $model->id }}"
                                class="flex-1 bg-purple-600 hover:bg-purple-700 py-2 rounded-xl text-center text-sm font-medium transition">
                                <i class="fas fa-wand-magic-sparkles mr-2"></i>Use
                            </a>
                            <a href="{{ route('models.show', $model->model_id) }}"
                                class="px-4 py-2 glass-card rounded-xl hover:bg-white/10 text-sm transition">
                                <i class="fas fa-info-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Image Generation Models -->
        <div class="mb-16">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-image text-blue-400 mr-3"></i>
                Image Generation
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($imageModels as $model)
                <div class="glass-card rounded-xl p-5 card-hover">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 flex items-center justify-center">
                            <i class="fas fa-image text-blue-400 text-xl"></i>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $model->category === 'flux' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400' }}">
                            {{ $categories[$model->category] ?? $model->category }}
                        </span>
                    </div>
                    <h3 class="font-bold mb-1">{{ $model->name }}</h3>
                    <p class="text-gray-500 text-xs mb-3 line-clamp-2">{{ $model->description }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-microchip mr-1"></i>{{ $model->vram_required_gb }}GB</span>
                        <span><i class="fas fa-bolt mr-1"></i>~{{ rand(5, 30) }}s</span>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('generate') }}?model={{ $model->id }}"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 py-2 rounded-lg text-center text-xs font-medium transition">
                            Use Model
                        </a>
                        <a href="{{ route('models.install', $model->model_id) }}"
                            class="px-3 py-2 glass rounded-lg hover:bg-white/10 text-xs transition" title="Install Guide">
                            <i class="fas fa-book"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Video Generation Models -->
        <div class="mb-16">
            <h2 class="text-2xl font-bold mb-6 flex items-center">
                <i class="fas fa-video text-pink-400 mr-3"></i>
                Video Generation
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($videoModels as $model)
                <div class="glass-card rounded-xl p-5 card-hover">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500/20 to-rose-500/20 flex items-center justify-center">
                            <i class="fas fa-video text-pink-400 text-xl"></i>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full bg-pink-500/20 text-pink-400">
                            {{ $categories[$model->category] ?? $model->category }}
                        </span>
                    </div>
                    <h3 class="font-bold mb-1">{{ $model->name }}</h3>
                    <p class="text-gray-500 text-xs mb-3 line-clamp-2">{{ $model->description }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span><i class="fas fa-microchip mr-1"></i>{{ $model->vram_required_gb }}GB</span>
                        <span><i class="fas fa-bolt mr-1"></i>~{{ rand(30, 120) }}s</span>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('generate') }}?model={{ $model->id }}"
                            class="flex-1 bg-pink-600 hover:bg-pink-700 py-2 rounded-lg text-center text-xs font-medium transition">
                            Use Model
                        </a>
                        <a href="{{ route('models.install', $model->model_id) }}"
                            class="px-3 py-2 glass rounded-lg hover:bg-white/10 text-xs transition" title="Install Guide">
                            <i class="fas fa-book"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- HuggingFace Integration Info -->
        <div class="glass-card rounded-2xl p-8">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-24 h-24 rounded-2xl bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-face-smile text-yellow-400 text-4xl"></i>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h3 class="text-2xl font-bold mb-2">Powered by HuggingFace</h3>
                    <p class="text-gray-400 mb-4">
                        We use open-source AI models from HuggingFace. All models are distributed across our GPU network
                        for fast, efficient generation. Nodes automatically download and cache models as needed.
                    </p>
                    <a href="https://huggingface.co" target="_blank" class="inline-flex items-center text-purple-400 hover:text-purple-300">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Visit HuggingFace
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
