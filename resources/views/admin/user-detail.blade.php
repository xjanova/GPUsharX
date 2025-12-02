@extends('layouts.admin')

@section('title', 'User Detail')
@section('header', 'User: ' . $user->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- User Info -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Profile Card -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-purple-500/20 rounded-full mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user text-purple-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold">{{ $user->name }}</h3>
                <p class="text-gray-400">{{ $user->email }}</p>
                <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm
                    {{ $user->status === 'active' ? 'bg-green-500/20 text-green-400' : '' }}
                    {{ $user->status === 'suspended' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                    {{ $user->status === 'banned' ? 'bg-red-500/20 text-red-400' : '' }}">
                    {{ ucfirst($user->status) }}
                </span>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Referral Code</span>
                    <span class="font-mono">{{ $user->referral_code }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Joined</span>
                    <span>{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Last Activity</span>
                    <span>{{ $user->last_activity?->diffForHumans() ?? 'Never' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Referrals</span>
                    <span>{{ $user->referrals->count() }}</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 space-y-2">
                @if($user->status !== 'banned')
                <form action="{{ route('admin.users.status', $user) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="banned">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 py-2 rounded-lg text-sm" onclick="return confirm('Ban this user?')">
                        <i class="fas fa-ban mr-2"></i>Ban User
                    </button>
                </form>
                @endif

                @if($user->status === 'suspended')
                <form action="{{ route('admin.users.status', $user) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 py-2 rounded-lg text-sm">
                        <i class="fas fa-check mr-2"></i>Reactivate
                    </button>
                </form>
                @endif

                @if($user->status === 'active')
                <form action="{{ route('admin.users.status', $user) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 py-2 rounded-lg text-sm">
                        <i class="fas fa-pause mr-2"></i>Suspend
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Earnings Summary -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
            <h4 class="font-semibold mb-4">Earnings Summary</h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Available Balance</span>
                    <span class="text-xl font-bold text-green-400">${{ number_format($earningStats['balance'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Pending</span>
                    <span class="text-yellow-400">${{ number_format($earningStats['pending'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Total Earned</span>
                    <span>${{ number_format($earningStats['total_earned'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Withdrawn</span>
                    <span>${{ number_format($earningStats['withdrawn'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- GPU Nodes -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h4 class="font-semibold">GPU Nodes ({{ $user->gpuNodes->count() }})</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700/50">
                        <tr>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Node ID</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">GPU</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Hashrate</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($user->gpuNodes as $node)
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm">{{ $node->node_id }}</td>
                            <td class="px-4 py-3">{{ $node->gpu_model }}</td>
                            <td class="px-4 py-3">{{ number_format($node->hashrate, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ $node->status === 'online' || $node->status === 'working' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                    {{ ucfirst($node->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">No GPU nodes</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Earnings -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h4 class="font-semibold">Recent Earnings</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700/50">
                        <tr>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Date</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Type</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Node</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Amount</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($recentEarnings as $earning)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-400">{{ $earning->created_at->format('M d, H:i') }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $earning->type) }}</td>
                            <td class="px-4 py-3 font-mono text-sm">{{ $earning->gpuNode?->node_id ?? '-' }}</td>
                            <td class="px-4 py-3 font-bold {{ $earning->net_amount >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                ${{ number_format($earning->net_amount, 4) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ $earning->status === 'confirmed' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $earning->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}">
                                    {{ ucfirst($earning->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No earnings yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payouts -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h4 class="font-semibold">Payout History</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700/50">
                        <tr>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Payout ID</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Amount</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Method</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Status</th>
                            <th class="text-left px-4 py-3 text-sm text-gray-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @forelse($user->payouts as $payout)
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm">{{ $payout->payout_id }}</td>
                            <td class="px-4 py-3 font-bold">${{ number_format($payout->amount, 2) }}</td>
                            <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $payout->payment_method) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs
                                    {{ $payout->status === 'completed' ? 'bg-green-500/20 text-green-400' : '' }}
                                    {{ $payout->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $payout->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}">
                                    {{ ucfirst($payout->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-400">{{ $payout->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No payouts yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
