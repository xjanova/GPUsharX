@extends('layouts.admin')

@section('title', 'Payouts')
@section('header', 'Payout Management')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-yellow-400">${{ number_format($payoutStats['pending'], 2) }}</p>
        <p class="text-gray-400 text-sm">Pending</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-blue-400">${{ number_format($payoutStats['processing'], 2) }}</p>
        <p class="text-gray-400 text-sm">Processing</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-green-400">${{ number_format($payoutStats['completed_today'], 2) }}</p>
        <p class="text-gray-400 text-sm">Completed Today</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold">${{ number_format($payoutStats['total_paid'], 2) }}</p>
        <p class="text-gray-400 text-sm">Total Paid</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.payouts') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Status</label>
            <select name="status" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Payouts Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Payout ID</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">User</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Amount</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Method</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Status</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Requested</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($payouts as $payout)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 font-mono text-sm">{{ $payout->payout_id }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.users.detail', $payout->user) }}" class="text-purple-400 hover:text-purple-300">
                            {{ $payout->user->name }}
                        </a>
                    </td>
                    <td class="px-6 py-4 font-bold">${{ number_format($payout->amount, 2) }}</td>
                    <td class="px-6 py-4 capitalize">{{ str_replace('_', ' ', $payout->payment_method) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $payout->status === 'completed' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $payout->status === 'processing' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $payout->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $payout->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}">
                            {{ ucfirst($payout->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">{{ $payout->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-4">
                        @if($payout->status === 'pending')
                        <form action="{{ route('admin.payouts.process', $payout) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="action" value="process">
                            <button type="submit" class="text-blue-400 hover:text-blue-300 mr-2" title="Start Processing">
                                <i class="fas fa-play"></i>
                            </button>
                        </form>
                        @endif

                        @if($payout->status === 'processing')
                        <button onclick="showCompleteModal('{{ $payout->id }}')" class="text-green-400 hover:text-green-300 mr-2" title="Mark Complete">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="showFailModal('{{ $payout->id }}')" class="text-red-400 hover:text-red-300" title="Mark Failed">
                            <i class="fas fa-times"></i>
                        </button>
                        @endif

                        @if($payout->status === 'completed' && $payout->transaction_id)
                        <span class="text-gray-400 text-xs">{{ $payout->transaction_id }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">No payouts found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payouts->hasPages())
    <div class="px-6 py-4 border-t border-gray-700">
        {{ $payouts->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- Complete Modal -->
<div id="completeModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Complete Payout</h3>
        <form id="completeForm" method="POST">
            @csrf
            <input type="hidden" name="action" value="complete">
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-2">Transaction ID *</label>
                <input type="text" name="transaction_id" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg">Complete</button>
                <button type="button" onclick="hideModal('completeModal')" class="bg-gray-600 hover:bg-gray-500 px-6 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Fail Modal -->
<div id="failModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Fail Payout</h3>
        <form id="failForm" method="POST">
            @csrf
            <input type="hidden" name="action" value="fail">
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-2">Reason *</label>
                <textarea name="reason" required rows="3"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-lg">Fail Payout</button>
                <button type="button" onclick="hideModal('failModal')" class="bg-gray-600 hover:bg-gray-500 px-6 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showCompleteModal(payoutId) {
    document.getElementById('completeForm').action = `/admin/payouts/${payoutId}/process`;
    document.getElementById('completeModal').classList.remove('hidden');
    document.getElementById('completeModal').classList.add('flex');
}

function showFailModal(payoutId) {
    document.getElementById('failForm').action = `/admin/payouts/${payoutId}/process`;
    document.getElementById('failModal').classList.remove('hidden');
    document.getElementById('failModal').classList.add('flex');
}

function hideModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}
</script>
@endpush
