@extends('layouts.app')

@section('title', 'Install ' . $model->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('models') }}" class="text-gray-400 hover:text-white transition">Models</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('models.show', $model->model_id) }}" class="text-gray-400 hover:text-white transition">{{ $model->name }}</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white">Installation Guide</span>
        </nav>
    </div>

    <!-- Header -->
    <div class="glass-card rounded-2xl p-6 mb-8">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Installing {{ $model->name }}</h1>
                <p class="text-gray-400">Complete installation guide for {{ $model->huggingface_id }}</p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-8">
        <!-- Navigation -->
        <div class="lg:col-span-1">
            <div class="glass-card rounded-2xl p-4 sticky top-4">
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Steps</h3>
                <nav class="space-y-2">
                    @foreach($installSteps as $index => $step)
                    <a href="#step-{{ $index }}" class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5 hover:text-white transition">
                        {{ $step['title'] }}
                    </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- Steps -->
        <div class="lg:col-span-3 space-y-8">
            @foreach($installSteps as $index => $step)
            <div id="step-{{ $index }}" class="glass-card rounded-2xl p-6 scroll-mt-4">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white text-sm font-bold">
                        {{ $index + 1 }}
                    </span>
                    {{ preg_replace('/^\d+\.\s*/', '', $step['title']) }}
                </h2>

                <div class="prose prose-invert max-w-none">
                    <p class="text-gray-300 whitespace-pre-line">{{ $step['content'] }}</p>
                </div>

                @if($step['code'])
                <div class="mt-4 relative">
                    <div class="absolute top-3 right-3 z-10">
                        <button onclick="copyCode(this)" class="p-2 rounded-lg bg-white/10 text-gray-400 hover:text-white hover:bg-white/20 transition" title="Copy code">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                    <pre class="bg-black/50 rounded-xl p-4 overflow-x-auto"><code class="text-sm text-gray-300 font-mono">{{ $step['code'] }}</code></pre>
                </div>
                @endif
            </div>
            @endforeach

            <!-- Additional Resources -->
            <div class="glass-card rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Additional Resources</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="https://huggingface.co/{{ $model->huggingface_id }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition">
                        <div class="w-10 h-10 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-400" viewBox="0 0 95 88" fill="currentColor">
                                <path d="M47.21 0c-25.32 0-45.87 20.17-45.87 45.05 0 20.01 13.41 36.91 31.9 42.69 2.34.43 3.21-.98 3.21-2.17 0-1.07-.04-4.62-.06-8.37-12.98 2.77-15.72-5.48-15.72-5.48-2.12-5.31-5.18-6.72-5.18-6.72-4.23-2.85.32-2.79.32-2.79 4.68.33 7.14 4.74 7.14 4.74 4.16 7.03 10.91 5 13.57 3.82.42-2.97 1.63-5 2.96-6.15-10.36-1.16-21.26-5.11-21.26-22.73 0-5.02 1.82-9.13 4.81-12.35-.48-1.16-2.08-5.84.46-12.17 0 0 3.92-1.24 12.84 4.71 3.73-1.02 7.72-1.53 11.69-1.55 3.97.02 7.96.53 11.69 1.55 8.92-5.95 12.83-4.71 12.83-4.71 2.55 6.33.95 11.01.47 12.17 2.99 3.22 4.8 7.33 4.8 12.35 0 17.68-10.92 21.55-21.32 22.69 1.68 1.42 3.17 4.23 3.17 8.53 0 6.16-.06 11.13-.06 12.64 0 1.21.85 2.62 3.22 2.18 18.48-5.8 31.86-22.69 31.86-42.69C93.08 20.17 72.53 0 47.21 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium">HuggingFace Model Card</p>
                            <p class="text-gray-400 text-sm">View documentation and examples</p>
                        </div>
                    </a>
                    <a href="https://github.com/huggingface/diffusers" target="_blank" rel="noopener"
                       class="flex items-center gap-3 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition">
                        <div class="w-10 h-10 rounded-lg bg-gray-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium">Diffusers Library</p>
                            <p class="text-gray-400 text-sm">GitHub documentation</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- CTA -->
            <div class="flex items-center justify-between gap-4 pt-4">
                <a href="{{ route('models.show', $model->model_id) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    Back to Model
                </a>
                <a href="{{ route('generate') }}?model={{ $model->id }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Generate Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyCode(button) {
    const codeBlock = button.closest('.relative').querySelector('code');
    navigator.clipboard.writeText(codeBlock.textContent).then(() => {
        button.innerHTML = '<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        setTimeout(() => {
            button.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
        }, 2000);
    });
}
</script>
@endsection
