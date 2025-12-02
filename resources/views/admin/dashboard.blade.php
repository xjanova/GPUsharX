@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard Overview')

@section('content')
<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Users -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Users</p>
                <p class="text-3xl font-bold">{{ number_format($stats['total_users']) }}</p>
                <p class="text-green-400 text-sm mt-1">+{{ $stats['new_users_today'] }} today</p>
            </div>
            <div class="bg-purple-500/20 p-3 rounded-lg">
                <i class="fas fa-users text-purple-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Active Nodes -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Active Nodes</p>
                <p class="text-3xl font-bold">{{ number_format($stats['active_nodes']) }}</p>
                <p class="text-gray-500 text-sm mt-1">of {{ number_format($stats['total_nodes']) }} total</p>
            </div>
            <div class="bg-green-500/20 p-3 rounded-lg">
                <i class="fas fa-server text-green-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Pool Hashrate -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Pool Hashrate</p>
                <p class="text-3xl font-bold">{{ number_format($stats['total_hashrate'], 2) }}</p>
                <p class="text-gray-500 text-sm mt-1">TH/s equivalent</p>
            </div>
            <div class="bg-blue-500/20 p-3 rounded-lg">
                <i class="fas fa-tachometer-alt text-blue-400 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Platform Revenue -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Platform Revenue</p>
                <p class="text-3xl font-bold">${{ number_format($stats['platform_revenue'], 2) }}</p>
                <p class="text-gray-500 text-sm mt-1">total fees collected</p>
            </div>
            <div class="bg-yellow-500/20 p-3 rounded-lg">
                <i class="fas fa-coins text-yellow-400 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Second Row Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Jobs Status -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Jobs Status</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-400">Pending/Queued</span>
                <span class="bg-yellow-500/20 text-yellow-400 px-3 py-1 rounded-full text-sm">{{ $stats['pending_jobs'] }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400">Processing</span>
                <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-sm">{{ $stats['processing_jobs'] }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-400">Completed Today</span>
                <span class="bg-green-500/20 text-green-400 px-3 py-1 rounded-full text-sm">{{ $stats['completed_jobs_today'] }}</span>
            </div>
        </div>
    </div>

    <!-- Earnings Distributed -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Earnings</h3>
        <div class="text-center">
            <p class="text-4xl font-bold text-green-400">${{ number_format($stats['total_earnings_distributed'], 2) }}</p>
            <p class="text-gray-400 mt-2">Total distributed to users</p>
        </div>
    </div>

    <!-- Pending Payouts -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Pending Payouts</h3>
        <div class="text-center">
            <p class="text-4xl font-bold text-orange-400">{{ $stats['pending_payouts'] }}</p>
            <p class="text-gray-400 mt-2">${{ number_format($stats['pending_payout_amount'], 2) }} to process</p>
            <a href="{{ route('admin.payouts') }}" class="inline-block mt-3 text-purple-400 hover:text-purple-300">
                View All <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Pool Activity Chart -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Pool Activity (Last 7 Days)</h3>
        <canvas id="activityChart" height="200"></canvas>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4">Revenue (Last 7 Days)</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
</div>

<!-- Tables Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Nodes -->
    <div class="bg-gray-800 rounded-xl border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Top Performing Nodes</h3>
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
                    @forelse($topNodes as $node)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-4 py-3 font-mono text-sm">{{ $node->node_id }}</td>
                        <td class="px-4 py-3">{{ $node->gpu_model }}</td>
                        <td class="px-4 py-3">{{ number_format($node->hashrate, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs {{ $node->status === 'working' ? 'bg-blue-500/20 text-blue-400' : ($node->status === 'online' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400') }}">
                                {{ ucfirst($node->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No active nodes</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Jobs -->
    <div class="bg-gray-800 rounded-xl border border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold">Recent Jobs</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-sm text-gray-400">Job ID</th>
                        <th class="text-left px-4 py-3 text-sm text-gray-400">Type</th>
                        <th class="text-left px-4 py-3 text-sm text-gray-400">Progress</th>
                        <th class="text-left px-4 py-3 text-sm text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($recentJobs as $job)
                    <tr class="hover:bg-gray-700/50">
                        <td class="px-4 py-3 font-mono text-sm">{{ $job->job_id }}</td>
                        <td class="px-4 py-3">{{ ucfirst($job->type) }}</td>
                        <td class="px-4 py-3">
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $job->progress_percentage }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $job->completed_chunks }}/{{ $job->total_chunks }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                {{ $job->status === 'completed' ? 'bg-green-500/20 text-green-400' : '' }}
                                {{ $job->status === 'processing' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                {{ $job->status === 'pending' || $job->status === 'queued' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                {{ $job->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No jobs found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from backend
    const chartData = @json($chartData);
    const labels = chartData.map(d => d.date);
    const activeNodes = chartData.map(d => d.active_nodes);
    const hashrate = chartData.map(d => d.total_hashrate);
    const revenue = chartData.map(d => d.platform_revenue);
    const distributed = chartData.map(d => d.credits_distributed);

    // Activity Chart
    new Chart(document.getElementById('activityChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Active Nodes',
                data: activeNodes,
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Pool Hashrate',
                data: hashrate,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: { intersect: false },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#9ca3af' }},
                y1: { position: 'right', grid: { display: false }, ticks: { color: '#9ca3af' }},
                x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#9ca3af' }}
            },
            plugins: { legend: { labels: { color: '#9ca3af' }}}
        }
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Platform Fee',
                data: revenue,
                backgroundColor: 'rgba(168, 85, 247, 0.8)',
                borderRadius: 4
            }, {
                label: 'Distributed',
                data: distributed,
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#9ca3af' }},
                x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#9ca3af' }}
            },
            plugins: { legend: { labels: { color: '#9ca3af' }}}
        }
    });
});
</script>
@endpush
