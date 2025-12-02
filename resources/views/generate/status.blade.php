@extends('layouts.app')

@section('title', 'Generation Status')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Back button -->
        <a href="{{ route('generate') }}" class="inline-flex items-center text-gray-400 hover:text-white mb-8">
            <i class="fas fa-arrow-left mr-2"></i>Back to Generate
        </a>

        <div class="glass-card rounded-3xl overflow-hidden">
            <!-- Result Preview -->
            <div class="aspect-video bg-gray-900 relative flex items-center justify-center" id="resultContainer">
                @if($job->status === 'completed' && $job->result_url)
                    @if($job->type === 'video')
                    <video src="{{ $job->result_url }}" controls class="max-h-full max-w-full"></video>
                    @else
                    <img src="{{ $job->result_url }}" alt="Generated Image" class="max-h-full max-w-full object-contain">
                    @endif
                @elseif($job->status === 'failed')
                <div class="text-center text-red-400">
                    <i class="fas fa-exclamation-triangle text-6xl mb-4"></i>
                    <p class="text-lg">Generation Failed</p>
                    <p class="text-sm text-gray-500 mt-2">{{ $job->error_message }}</p>
                </div>
                @else
                <div class="text-center" id="loadingState">
                    <div class="relative w-32 h-32 mx-auto mb-6">
                        <div class="absolute inset-0 border-4 border-purple-500/20 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-transparent border-t-purple-500 rounded-full animate-spin"></div>
                        <div class="absolute inset-4 border-4 border-transparent border-t-pink-500 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                        <div class="absolute inset-8 border-4 border-transparent border-t-blue-500 rounded-full animate-spin" style="animation-duration: 2s;"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-2xl font-bold gradient-text" id="progressText">{{ $job->progress }}%</span>
                        </div>
                    </div>
                    <p class="text-gray-400" id="statusText">
                        @if($job->status === 'queued')
                        Waiting in queue...
                        @else
                        Generating your {{ $job->type }}...
                        @endif
                    </p>
                    <div class="w-64 mx-auto mt-4 h-2 bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full progress-animated rounded-full transition-all duration-500" id="progressBar" style="width: {{ $job->progress }}%"></div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Details -->
            <div class="p-8">
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $job->status === 'completed' ? 'bg-green-500/20 text-green-400' :
                           ($job->status === 'processing' ? 'bg-yellow-500/20 text-yellow-400' :
                           ($job->status === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-gray-500/20 text-gray-400')) }}"
                        id="statusBadge">
                        <i class="fas {{ $job->status === 'completed' ? 'fa-check' :
                           ($job->status === 'processing' ? 'fa-spinner fa-spin' :
                           ($job->status === 'failed' ? 'fa-times' : 'fa-clock')) }} mr-1"></i>
                        {{ ucfirst($job->status) }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm bg-purple-500/20 text-purple-400">
                        {{ $job->aiModel->name ?? 'Unknown Model' }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm bg-blue-500/20 text-blue-400">
                        {{ ucfirst($job->type) }}
                    </span>
                </div>

                <h2 class="text-xl font-bold mb-4">Prompt</h2>
                <p class="text-gray-300 mb-6 p-4 glass rounded-xl">{{ $job->prompt }}</p>

                @if($job->negative_prompt)
                <h3 class="text-sm font-medium text-gray-400 mb-2">Negative Prompt</h3>
                <p class="text-gray-500 mb-6 text-sm">{{ $job->negative_prompt }}</p>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Size</p>
                        <p class="font-medium">{{ $job->params['width'] ?? '1024' }} x {{ $job->params['height'] ?? '1024' }}</p>
                    </div>
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Steps</p>
                        <p class="font-medium">{{ $job->params['steps'] ?? '30' }}</p>
                    </div>
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">CFG Scale</p>
                        <p class="font-medium">{{ $job->params['cfg_scale'] ?? '7.5' }}</p>
                    </div>
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Seed</p>
                        <p class="font-medium font-mono text-sm">{{ $job->params['seed'] ?? 'Random' }}</p>
                    </div>
                </div>

                @if($job->status === 'completed')
                <div class="flex flex-wrap gap-4">
                    <a href="{{ $job->result_url }}" download class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 py-3 rounded-xl font-medium text-center transition">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                    <button onclick="shareResult()" class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition">
                        <i class="fas fa-share-alt mr-2"></i>Share
                    </button>
                    <a href="{{ route('generate') }}?prompt={{ urlencode($job->prompt) }}" class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition">
                        <i class="fas fa-redo mr-2"></i>Regenerate
                    </a>
                </div>
                @endif

                @if($job->status === 'failed')
                <div class="mt-6">
                    <a href="{{ route('generate') }}" class="inline-block bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-xl font-medium transition">
                        <i class="fas fa-redo mr-2"></i>Try Again
                    </a>
                </div>
                @endif

                <div class="mt-8 pt-6 border-t border-gray-800 flex items-center justify-between text-sm text-gray-500">
                    <span>Job ID: {{ $job->job_id }}</span>
                    <span>Created: {{ $job->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($job->status !== 'completed' && $job->status !== 'failed')
@push('scripts')
<script>
    // Poll for status updates
    const jobId = '{{ $job->job_id }}';
    let pollInterval;

    function checkStatus() {
        fetch(`/generate/${jobId}/status`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('progressText').textContent = data.progress + '%';
                document.getElementById('progressBar').style.width = data.progress + '%';

                if (data.status === 'processing') {
                    document.getElementById('statusText').textContent = 'Generating your {{ $job->type }}...';
                }

                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(pollInterval);
                    location.reload();
                }
            })
            .catch(console.error);
    }

    pollInterval = setInterval(checkStatus, 2000);
    checkStatus();
</script>
@endpush
@endif

@push('scripts')
<script>
    function shareResult() {
        if (navigator.share) {
            navigator.share({
                title: 'AI Generated Art',
                text: '{{ Str::limit($job->prompt, 100) }}',
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('Link copied to clipboard!');
        }
    }
</script>
@endpush
@endsection
