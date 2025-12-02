@extends('layouts.app')

@section('title', 'Gallery - AI Creations')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-white mb-4">
            <span class="bg-gradient-to-r from-purple-400 to-pink-400 bg-clip-text text-transparent">
                AI Gallery
            </span>
        </h1>
        <p class="text-gray-400 max-w-2xl mx-auto">
            ผลงานสร้างสรรค์จาก AI โดยสมาชิกในชุมชนของเรา
        </p>
    </div>

    <!-- Filters -->
    <div class="glass-card rounded-2xl p-6 mb-8">
        <div class="flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-2">
                <button class="px-4 py-2 rounded-lg bg-purple-600 text-white text-sm font-medium">
                    All
                </button>
                <button class="px-4 py-2 rounded-lg bg-white/5 text-gray-300 text-sm font-medium hover:bg-white/10">
                    Images
                </button>
                <button class="px-4 py-2 rounded-lg bg-white/5 text-gray-300 text-sm font-medium hover:bg-white/10">
                    Videos
                </button>
            </div>
            <div class="flex gap-2">
                <select class="px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-300 text-sm">
                    <option value="latest">Latest</option>
                    <option value="popular">Most Popular</option>
                    <option value="trending">Trending</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Gallery Grid -->
    @if($gallery->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($gallery as $item)
        <div class="glass-card rounded-2xl overflow-hidden group cursor-pointer">
            <div class="relative aspect-square">
                @if($item->type === 'video')
                <video class="w-full h-full object-cover" muted loop>
                    <source src="{{ Storage::url($item->output_file) }}" type="video/mp4">
                </video>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-12 h-12 rounded-full bg-black/50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 5v10l7-5-7-5z"/>
                        </svg>
                    </div>
                </div>
                @else
                <img src="{{ Storage::url($item->output_file) }}"
                     alt="{{ $item->prompt }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @endif

                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="absolute bottom-0 left-0 right-0 p-4">
                        <p class="text-white text-sm line-clamp-2 mb-2">{{ $item->prompt }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-purple-500 flex items-center justify-center text-xs font-bold text-white">
                                    {{ substr($item->user->name ?? 'A', 0, 1) }}
                                </div>
                                <span class="text-gray-300 text-xs">{{ $item->user->name ?? 'Anonymous' }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <button class="text-gray-300 hover:text-pink-400 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                                <span class="text-gray-400 text-xs">{{ $item->likes ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $gallery->links() }}
    </div>
    @else
    <div class="glass-card rounded-2xl p-12 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-purple-500/20 flex items-center justify-center">
            <svg class="w-10 h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-white mb-2">ยังไม่มีผลงานในแกลเลอรี่</h3>
        <p class="text-gray-400 mb-6">เริ่มสร้างภาพแรกของคุณเลย!</p>
        <a href="{{ route('generate') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            สร้างภาพใหม่
        </a>
    </div>
    @endif
</div>
@endsection
