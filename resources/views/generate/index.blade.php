@extends('layouts.app')

@section('title', 'Generate AI Art')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="gradient-text">AI Art Generator</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Create stunning images and videos using the power of distributed GPU computing.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Generation Form -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-3xl p-8">
                    <form action="{{ route('generate.create') }}" method="POST" id="generateForm">
                        @csrf

                        <!-- Model Selection -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-300 mb-3">
                                <i class="fas fa-cube text-purple-400 mr-2"></i>Select Model
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="modelGrid">
                                @foreach($featuredModels as $model)
                                <label class="model-card cursor-pointer">
                                    <input type="radio" name="model_id" value="{{ $model->id }}"
                                        class="hidden" {{ $loop->first ? 'checked' : '' }}
                                        data-type="{{ $model->type }}"
                                        data-vram="{{ $model->vram_required_mb }}">
                                    <div class="glass-card rounded-xl p-4 border-2 border-transparent transition-all hover:border-purple-500/50 model-option">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs px-2 py-1 rounded-full {{ $model->type === 'image' ? 'bg-blue-500/20 text-blue-400' : 'bg-pink-500/20 text-pink-400' }}">
                                                {{ ucfirst($model->type) }}
                                            </span>
                                            <span class="text-xs text-gray-500">{{ $model->vram_required_gb }}GB</span>
                                        </div>
                                        <h4 class="font-medium text-sm">{{ $model->name }}</h4>
                                        <p class="text-xs text-gray-500 mt-1">{{ Str::limit($model->description, 50) }}</p>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            <a href="{{ route('models') }}" class="inline-block mt-3 text-purple-400 hover:text-purple-300 text-sm">
                                <i class="fas fa-plus mr-1"></i> View all models
                            </a>
                        </div>

                        <!-- Prompt -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-magic text-purple-400 mr-2"></i>Prompt
                            </label>
                            <textarea name="prompt" rows="4" required
                                class="w-full glass-input rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none resize-none"
                                placeholder="Describe what you want to create... e.g., 'A majestic dragon flying over a crystal lake at sunset, ultra detailed, 8k'"></textarea>
                        </div>

                        <!-- Negative Prompt -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-ban text-red-400 mr-2"></i>Negative Prompt (Optional)
                            </label>
                            <textarea name="negative_prompt" rows="2"
                                class="w-full glass-input rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none resize-none"
                                placeholder="What to avoid... e.g., 'blurry, low quality, distorted'"></textarea>
                        </div>

                        <!-- Advanced Settings -->
                        <div class="mb-8">
                            <button type="button" onclick="toggleAdvanced()" class="text-purple-400 hover:text-purple-300 text-sm mb-4">
                                <i class="fas fa-sliders-h mr-2"></i>Advanced Settings
                                <i class="fas fa-chevron-down ml-2 transition-transform" id="advancedIcon"></i>
                            </button>

                            <div id="advancedSettings" class="hidden space-y-4">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Width</label>
                                        <select name="width" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                            <option value="512">512</option>
                                            <option value="768">768</option>
                                            <option value="1024" selected>1024</option>
                                            <option value="1280">1280</option>
                                            <option value="1536">1536</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Height</label>
                                        <select name="height" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                            <option value="512">512</option>
                                            <option value="768">768</option>
                                            <option value="1024" selected>1024</option>
                                            <option value="1280">1280</option>
                                            <option value="1536">1536</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Steps</label>
                                        <input type="number" name="steps" value="30" min="1" max="150"
                                            class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">CFG Scale</label>
                                        <input type="number" name="cfg_scale" value="7.5" min="1" max="30" step="0.5"
                                            class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Seed (Optional)</label>
                                        <input type="number" name="seed" placeholder="Random"
                                            class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Visibility</label>
                                        <select name="visibility" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                            <option value="private">Private</option>
                                            <option value="unlisted">Unlisted</option>
                                            <option value="public">Public</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" id="generateBtn"
                            class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 py-4 rounded-xl font-bold text-lg transition glow-purple flex items-center justify-center">
                            <i class="fas fa-wand-magic-sparkles mr-3"></i>
                            Generate
                        </button>

                        @guest
                        <p class="text-center text-gray-500 text-sm mt-4">
                            <a href="{{ route('login') }}" class="text-purple-400 hover:underline">Login</a> or
                            <a href="{{ route('register') }}" class="text-purple-400 hover:underline">Register</a> to generate
                        </p>
                        @endguest
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-purple-400 mr-2"></i>
                        Platform Stats
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Active GPUs</span>
                            <span class="text-green-400 font-bold">{{ rand(50, 150) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Total Hashrate</span>
                            <span class="text-purple-400 font-bold">{{ rand(500, 1500) }} TH/s</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Queue</span>
                            <span class="text-yellow-400 font-bold">{{ rand(5, 30) }} jobs</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-400">Avg. Time</span>
                            <span class="text-blue-400 font-bold">~{{ rand(10, 45) }}s</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Generations -->
                @auth
                @if($recentGenerations->count() > 0)
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-history text-purple-400 mr-2"></i>
                        Your Recent
                    </h3>
                    <div class="space-y-3">
                        @foreach($recentGenerations->take(5) as $gen)
                        <a href="{{ route('generate.status', $gen->job_id) }}"
                            class="block glass rounded-lg p-3 hover:bg-white/5 transition">
                            <div class="flex items-center justify-between">
                                <span class="text-sm truncate flex-1">{{ Str::limit($gen->prompt, 30) }}</span>
                                <span class="text-xs ml-2 {{ $gen->status === 'completed' ? 'text-green-400' : ($gen->status === 'processing' ? 'text-yellow-400' : 'text-gray-400') }}">
                                    {{ ucfirst($gen->status) }}
                                </span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                @endauth

                <!-- Tips -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>
                        Pro Tips
                    </h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            Be specific and descriptive in your prompts
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            Use negative prompts to avoid unwanted elements
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            Higher steps = better quality but slower
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-400 mr-2 mt-1"></i>
                            Save seeds to reproduce results
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Public Gallery Preview -->
        @if($publicGallery->count() > 0)
        <div class="mt-16">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">
                    <i class="fas fa-fire text-orange-400 mr-3"></i>
                    Trending Creations
                </h2>
                <a href="{{ route('gallery') }}" class="text-purple-400 hover:text-purple-300">
                    View All <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($publicGallery->take(10) as $gen)
                <div class="glass-card rounded-xl overflow-hidden card-hover group">
                    <div class="aspect-square bg-gray-800 relative">
                        @if($gen->result_thumbnail)
                        <img src="{{ $gen->result_thumbnail }}" alt="" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-gray-600">
                            <i class="fas fa-image text-4xl"></i>
                        </div>
                        @endif
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-end p-3">
                            <p class="text-xs text-gray-300 line-clamp-2">{{ $gen->prompt }}</p>
                        </div>
                    </div>
                    <div class="p-3 flex items-center justify-between">
                        <span class="text-xs text-gray-400">{{ $gen->user->name ?? 'Anonymous' }}</span>
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-heart mr-1"></i>{{ $gen->likes }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Model selection
    document.querySelectorAll('.model-card input').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.model-option').forEach(opt => {
                opt.classList.remove('border-purple-500', 'glow-purple');
            });
            this.parentElement.querySelector('.model-option').classList.add('border-purple-500', 'glow-purple');
        });
    });

    // Initialize first model as selected
    document.querySelector('.model-card input:checked')?.parentElement.querySelector('.model-option').classList.add('border-purple-500');

    // Advanced settings toggle
    function toggleAdvanced() {
        const settings = document.getElementById('advancedSettings');
        const icon = document.getElementById('advancedIcon');
        settings.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    // Form submission
    document.getElementById('generateForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Creating...';
    });
</script>
@endpush
@endsection
