@extends('layouts.admin')

@section('title', 'Referral Management')
@section('header', 'Referral Management')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Referrers</p>
                <p class="text-2xl font-bold text-white">{{ number_format($stats['total_users_with_referrals']) }}</p>
            </div>
            <i class="fas fa-users text-3xl text-purple-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Referral Tree</p>
                <p class="text-2xl font-bold text-blue-400">{{ number_format($stats['total_referral_trees']) }}</p>
            </div>
            <i class="fas fa-sitemap text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Commission Paid</p>
                <p class="text-2xl font-bold text-green-400">${{ number_format($stats['total_commission_paid'], 2) }}</p>
            </div>
            <i class="fas fa-check-circle text-3xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Pending Commission</p>
                <p class="text-2xl font-bold text-yellow-400">${{ number_format($stats['pending_commission'], 2) }}</p>
            </div>
            <i class="fas fa-clock text-3xl text-yellow-400"></i>
        </div>
    </div>
</div>

<!-- Level Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Level 1 (Direct)</p>
                <p class="text-xl font-bold text-white">{{ number_format($stats['level_1_count']) }}</p>
                <p class="text-xs text-green-400">{{ $commissionRates[1] }}% Commission</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-600/20 flex items-center justify-center">
                <span class="text-green-400 font-bold">L1</span>
            </div>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Level 2</p>
                <p class="text-xl font-bold text-white">{{ number_format($stats['level_2_count']) }}</p>
                <p class="text-xs text-blue-400">{{ $commissionRates[2] }}% Commission</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-600/20 flex items-center justify-center">
                <span class="text-blue-400 font-bold">L2</span>
            </div>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Level 3</p>
                <p class="text-xl font-bold text-white">{{ number_format($stats['level_3_count']) }}</p>
                <p class="text-xs text-purple-400">{{ $commissionRates[3] }}% Commission</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-600/20 flex items-center justify-center">
                <span class="text-purple-400 font-bold">L3</span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Referrers -->
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-trophy text-yellow-400 mr-2"></i>Top Referrers
            </h3>
        </div>
        <div class="divide-y divide-gray-700">
            @forelse($topReferrers as $index => $user)
            <div class="p-4 flex items-center justify-between hover:bg-gray-750">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $index < 3 ? 'bg-yellow-600/20 text-yellow-400' : 'bg-gray-700 text-gray-400' }}">
                        {{ $index + 1 }}
                    </div>
                    <div>
                        <p class="font-medium text-white">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-purple-400">{{ $user->referrals_count }}</p>
                    <p class="text-xs text-gray-400">referrals</p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-400">
                No referrers yet
            </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Commission Earnings -->
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="p-4 border-b border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-coins text-green-400 mr-2"></i>Recent Commission Earnings
            </h3>
            <button onclick="payAllPending()" id="btn-pay-all" class="px-3 py-1 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition {{ $stats['pending_commission'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $stats['pending_commission'] <= 0 ? 'disabled' : '' }}>
                <i class="fas fa-money-bill-wave mr-1"></i>Pay All Pending
            </button>
        </div>
        <div class="divide-y divide-gray-700 max-h-[400px] overflow-y-auto">
            @forelse($recentEarnings as $earning)
            <div class="p-4 hover:bg-gray-750" id="earning-{{ $earning->id }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <div>
                            <p class="font-medium text-white">{{ $earning->user->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-gray-400">
                                From: {{ $earning->fromUser->name ?? 'Unknown' }}
                                <span class="ml-2 px-1 py-0.5 bg-purple-600/20 text-purple-400 rounded text-xs">Level {{ $earning->level }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-green-400">${{ number_format($earning->commission_amount, 2) }}</p>
                        <div class="flex items-center gap-2 justify-end">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $earning->status === 'paid' ? 'bg-green-600/20 text-green-400' : 'bg-yellow-600/20 text-yellow-400' }}">
                                {{ ucfirst($earning->status) }}
                            </span>
                            @if($earning->status === 'pending')
                            <button onclick="payCommission({{ $earning->id }})" class="text-xs px-2 py-0.5 bg-green-600 hover:bg-green-700 text-white rounded transition">
                                Pay
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ $earning->created_at->diffForHumans() }} | Original: ${{ number_format($earning->original_amount, 2) }} x {{ $earning->commission_rate }}%</p>
            </div>
            @empty
            <div class="p-8 text-center text-gray-400">
                No commission earnings yet
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

async function payCommission(earningId) {
    if (!confirm('Pay this commission?')) return;

    try {
        const response = await fetch(`/admin/referral-earnings/${earningId}/pay`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function payAllPending() {
    if (!confirm('Pay all pending commissions?')) return;

    const btn = document.getElementById('btn-pay-all');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i>Processing...';

    try {
        const response = await fetch('/admin/referral-earnings/bulk-pay', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ pay_all: true })
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-money-bill-wave mr-1"></i>Pay All Pending';
    }
}
</script>
@endpush
