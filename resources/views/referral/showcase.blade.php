@extends('layouts.app')

@section('title', 'Earnings Showcase')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-white mb-4">
            <span class="bg-gradient-to-r from-green-400 to-emerald-400 bg-clip-text text-transparent">
                Earnings Showcase
            </span>
        </h1>
        <p class="text-gray-400 max-w-2xl mx-auto">
            ดูรายได้จริงจากสมาชิกในชุมชนของเรา - แบ่งปันความสำเร็จและสร้างแรงบันดาลใจให้กัน!
        </p>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Top Earners -->
        <div class="lg:col-span-1">
            <div class="glass-card rounded-2xl p-6 sticky top-4">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Top Earners
                </h3>

                <div class="space-y-4">
                    @forelse($topEarners as $index => $earner)
                    <div class="flex items-center gap-3 p-3 rounded-xl {{ $index < 3 ? 'bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/20' : 'bg-white/5' }}">
                        <!-- Rank -->
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                            {{ $index === 0 ? 'bg-yellow-500 text-black' : '' }}
                            {{ $index === 1 ? 'bg-gray-300 text-black' : '' }}
                            {{ $index === 2 ? 'bg-orange-600 text-white' : '' }}
                            {{ $index > 2 ? 'bg-white/10 text-gray-400' : '' }}">
                            {{ $index + 1 }}
                        </div>

                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                            {{ substr($earner->name, 0, 1) }}
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-medium truncate">{{ $earner->name }}</p>
                            <p class="text-green-400 text-sm font-semibold">${{ number_format($earner->total_earned, 2) }}</p>
                        </div>

                        <!-- Crown for top 3 -->
                        @if($index < 3)
                        <div class="text-yellow-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-400">
                        <p>No earnings data yet</p>
                    </div>
                    @endforelse
                </div>

                @auth
                <div class="mt-6 pt-6 border-t border-white/10">
                    <a href="{{ route('referral') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Start Referring
                    </a>
                </div>
                @endauth
            </div>
        </div>

        <!-- Showcases Grid -->
        <div class="lg:col-span-2">
            @auth
            <!-- Create Showcase Form -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <h3 class="text-lg font-semibold text-white mb-4">Share Your Earnings</h3>
                <form action="{{ route('referral.showcase.create') }}" method="POST">
                    @csrf
                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Period</label>
                            <select name="period" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="total">Total</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Visibility</label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_public" value="1" checked class="w-5 h-5 rounded bg-white/5 border-white/10 text-purple-500 focus:ring-purple-500 focus:ring-offset-0">
                                <span class="text-gray-300">Show publicly</span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-300 text-sm mb-2">Message (optional)</label>
                        <textarea name="message" rows="2" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition resize-none" placeholder="Share your success story..."></textarea>
                    </div>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium hover:opacity-90 transition">
                        Share My Earnings
                    </button>
                </form>
            </div>
            @endauth

            <!-- Showcase Cards -->
            @if($showcases->count() > 0)
            <div class="grid md:grid-cols-2 gap-6">
                @foreach($showcases as $showcase)
                <div class="glass-card rounded-2xl p-6 hover:border-green-500/30 transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center text-white font-bold text-lg">
                                {{ substr($showcase->user->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-white font-medium">{{ $showcase->user->name }}</p>
                                <p class="text-gray-400 text-sm">{{ $showcase->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @if($showcase->is_verified)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Verified
                        </span>
                        @endif
                    </div>

                    <!-- Amount -->
                    <div class="bg-gradient-to-r from-green-500/10 to-emerald-500/10 rounded-xl p-4 mb-4">
                        <p class="text-gray-400 text-sm mb-1">{{ ucfirst($showcase->period) }} Earnings</p>
                        <p class="text-3xl font-bold text-green-400">${{ number_format($showcase->display_amount, 2) }}</p>
                    </div>

                    @if($showcase->message)
                    <p class="text-gray-300 text-sm mb-4">{{ $showcase->message }}</p>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-white/10">
                        <button class="flex items-center gap-2 text-gray-400 hover:text-pink-400 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span>{{ $showcase->likes }}</span>
                        </button>
                        @if($showcase->user->referral_code)
                        <a href="{{ route('register', ['ref' => $showcase->user->referral_code]) }}" class="text-sm text-purple-400 hover:text-purple-300 transition">
                            Join their team
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $showcases->links() }}
            </div>
            @else
            <div class="glass-card rounded-2xl p-12 text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-500/20 flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">No showcases yet</h3>
                <p class="text-gray-400 mb-6">Be the first to share your earnings!</p>
                @auth
                <button onclick="document.querySelector('form').scrollIntoView({behavior: 'smooth'})" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 text-white font-medium hover:opacity-90 transition">
                    Share My Earnings
                </button>
                @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition">
                    Join Now
                </a>
                @endauth
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
