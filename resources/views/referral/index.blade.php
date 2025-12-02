@extends('layouts.app')

@section('title', 'Referral Program')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <span class="gradient-text">Referral Program</span>
            </h1>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                Invite friends and earn commission on their earnings - up to 3 levels deep!
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="glass-card rounded-2xl p-6 text-center card-hover">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-users text-purple-400 text-2xl"></i>
                </div>
                <p class="text-3xl font-bold text-purple-400">{{ $directReferrals }}</p>
                <p class="text-gray-400 text-sm">Direct Referrals</p>
            </div>
            <div class="glass-card rounded-2xl p-6 text-center card-hover">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-sitemap text-blue-400 text-2xl"></i>
                </div>
                <p class="text-3xl font-bold text-blue-400">{{ $totalTeam }}</p>
                <p class="text-gray-400 text-sm">Total Team</p>
            </div>
            <div class="glass-card rounded-2xl p-6 text-center card-hover">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-400 text-2xl"></i>
                </div>
                <p class="text-3xl font-bold text-green-400">${{ number_format($user->referral_earnings ?? 0, 2) }}</p>
                <p class="text-gray-400 text-sm">Total Earned</p>
            </div>
            <div class="glass-card rounded-2xl p-6 text-center card-hover">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-percentage text-yellow-400 text-2xl"></i>
                </div>
                <p class="text-3xl font-bold text-yellow-400">{{ $commissionRates[1] }}%</p>
                <p class="text-gray-400 text-sm">Commission Rate</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Referral Link & QR -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Share Link -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-link text-purple-400 mr-3"></i>
                        Your Referral Link
                    </h3>
                    <div class="flex gap-3">
                        <input type="text" value="{{ $referralUrl }}" readonly id="referralLink"
                            class="flex-1 glass-input rounded-xl px-4 py-3 text-gray-300 font-mono text-sm">
                        <button onclick="copyLink()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-xl transition">
                            <i class="fas fa-copy mr-2"></i>Copy
                        </button>
                    </div>
                    <div class="mt-4 flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($referralUrl) }}" target="_blank"
                            class="flex-1 glass-card rounded-xl py-3 text-center hover:bg-blue-600/20 transition">
                            <i class="fab fa-facebook text-blue-500 mr-2"></i>Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($referralUrl) }}&text=Join%20GPU%20Share%20and%20earn!" target="_blank"
                            class="flex-1 glass-card rounded-xl py-3 text-center hover:bg-sky-600/20 transition">
                            <i class="fab fa-twitter text-sky-500 mr-2"></i>Twitter
                        </a>
                        <a href="https://line.me/R/msg/text/?{{ urlencode($referralUrl) }}" target="_blank"
                            class="flex-1 glass-card rounded-xl py-3 text-center hover:bg-green-600/20 transition">
                            <i class="fab fa-line text-green-500 mr-2"></i>LINE
                        </a>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-qrcode text-purple-400 mr-3"></i>
                        QR Code
                    </h3>
                    <div class="flex items-center gap-8">
                        <div class="bg-white p-4 rounded-2xl">
                            <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" class="w-48 h-48">
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-400 mb-4">Scan this QR code to register with your referral code.</p>
                            <p class="text-sm text-gray-500 mb-4">Your Code: <span class="text-purple-400 font-mono font-bold">{{ $user->referral_code }}</span></p>
                            <a href="{{ route('referral.qr') }}" download="gpu-share-referral-qr.png"
                                class="inline-block bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-xl transition">
                                <i class="fas fa-download mr-2"></i>Download QR
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Commission Tiers -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold mb-6 flex items-center">
                        <i class="fas fa-layer-group text-purple-400 mr-3"></i>
                        Commission Tiers
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                        @foreach($commissionRates as $level => $rate)
                        <div class="glass rounded-xl p-4 text-center {{ $level === 1 ? 'border border-purple-500/50' : '' }}">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gradient-to-br {{ $level === 1 ? 'from-purple-500 to-pink-500' : ($level === 2 ? 'from-blue-500 to-cyan-500' : 'from-green-500 to-emerald-500') }} flex items-center justify-center">
                                <span class="text-xl font-bold">{{ $level }}</span>
                            </div>
                            <p class="text-2xl font-bold {{ $level === 1 ? 'text-purple-400' : ($level === 2 ? 'text-blue-400' : 'text-green-400') }}">{{ $rate }}%</p>
                            <p class="text-sm text-gray-500">Level {{ $level }}</p>
                            <p class="text-xs text-gray-600 mt-1">
                                {{ $level === 1 ? 'Direct referrals' : ($level === 2 ? 'Their referrals' : '3rd level') }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Showcase Earnings -->
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-trophy text-yellow-400 mr-3"></i>
                        Showcase Earnings
                    </h3>
                    <p class="text-gray-400 text-sm mb-4">Show off your earnings and attract more referrals!</p>
                    <form action="{{ route('referral.showcase.create') }}" method="POST" class="space-y-4">
                        @csrf
                        <select name="period" class="w-full glass-input rounded-xl px-4 py-3">
                            <option value="daily">Today's Earnings</option>
                            <option value="weekly">This Week</option>
                            <option value="monthly">This Month</option>
                            <option value="total">All Time</option>
                        </select>
                        <textarea name="message" rows="2" placeholder="Add a message (optional)"
                            class="w-full glass-input rounded-xl px-4 py-3 resize-none text-sm"></textarea>
                        <label class="flex items-center gap-2 text-sm text-gray-400">
                            <input type="checkbox" name="is_public" value="1" checked class="rounded">
                            Make public
                        </label>
                        <button type="submit" class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-400 hover:to-orange-400 py-3 rounded-xl font-medium transition">
                            <i class="fas fa-share-alt mr-2"></i>Share Earnings
                        </button>
                    </form>
                </div>

                <!-- View Showcase -->
                <a href="{{ route('referral.showcase') }}" class="block glass-card rounded-2xl p-6 hover:bg-white/5 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold">Earnings Showcase</h4>
                            <p class="text-sm text-gray-400">See top earners</p>
                        </div>
                        <i class="fas fa-arrow-right text-purple-400"></i>
                    </div>
                </a>

                <!-- Referral Tree -->
                @if($referralTree->count() > 0)
                <div class="glass-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold mb-4">Your Team</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        @foreach($referralTree as $level => $members)
                        <div class="mb-3">
                            <p class="text-xs text-gray-500 mb-2">Level {{ $level }}</p>
                            @foreach($members as $member)
                            <div class="flex items-center justify-between py-2 border-b border-gray-800 last:border-0">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-purple-500/20 flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-purple-400 text-xs"></i>
                                    </div>
                                    <span class="text-sm">{{ $member->user->name ?? 'User' }}</span>
                                </div>
                                <span class="text-xs text-green-400">${{ number_format($member->total_earned, 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Referral Earnings -->
        @if($referralEarnings->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">
                <i class="fas fa-history text-purple-400 mr-3"></i>
                Recent Commission Earnings
            </h2>
            <div class="glass-card rounded-2xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-black/20">
                        <tr>
                            <th class="text-left px-6 py-4 text-gray-400 font-medium">From</th>
                            <th class="text-left px-6 py-4 text-gray-400 font-medium">Level</th>
                            <th class="text-right px-6 py-4 text-gray-400 font-medium">Original</th>
                            <th class="text-right px-6 py-4 text-gray-400 font-medium">Commission</th>
                            <th class="text-right px-6 py-4 text-gray-400 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($referralEarnings as $earning)
                        <tr class="border-t border-gray-800 hover:bg-white/5">
                            <td class="px-6 py-4">{{ $earning->fromUser->name ?? 'User' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs {{ $earning->level === 1 ? 'bg-purple-500/20 text-purple-400' : ($earning->level === 2 ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400') }}">
                                    Level {{ $earning->level }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-400">${{ number_format($earning->original_amount, 2) }}</td>
                            <td class="px-6 py-4 text-right text-green-400 font-medium">+${{ number_format($earning->commission_amount, 2) }}</td>
                            <td class="px-6 py-4 text-right text-gray-500 text-sm">{{ $earning->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $referralEarnings->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function copyLink() {
        const input = document.getElementById('referralLink');
        input.select();
        navigator.clipboard.writeText(input.value);
        alert('Referral link copied!');
    }
</script>
@endpush
@endsection
