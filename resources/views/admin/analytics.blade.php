@extends('layouts.admin')

@section('title', 'Analytics Dashboard')
@section('header', 'Analytics Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Users</p>
                <p class="text-2xl font-bold text-white">{{ number_format($stats['total_users']) }}</p>
                <p class="text-xs text-green-400">{{ $stats['active_users'] }} active (7d)</p>
            </div>
            <i class="fas fa-users text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">GPU Nodes</p>
                <p class="text-2xl font-bold text-white">{{ number_format($stats['total_nodes']) }}</p>
                <p class="text-xs text-green-400">{{ $stats['online_nodes'] }} online</p>
            </div>
            <i class="fas fa-server text-3xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Jobs</p>
                <p class="text-2xl font-bold text-white">{{ number_format($stats['total_jobs']) }}</p>
                <p class="text-xs text-green-400">{{ $stats['completed_jobs'] }} completed</p>
            </div>
            <i class="fas fa-tasks text-3xl text-purple-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Revenue</p>
                <p class="text-2xl font-bold text-white">${{ number_format($stats['total_earnings'], 2) }}</p>
                <p class="text-xs text-gray-400">{{ number_format($stats['total_credits_used']) }} credits used</p>
            </div>
            <i class="fas fa-dollar-sign text-3xl text-yellow-400"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Jobs Chart -->
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-chart-line mr-2 text-purple-400"></i>Jobs (Last 30 Days)
        </h3>
        <canvas id="jobsChart" height="200"></canvas>
    </div>

    <!-- Credits Chart -->
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-coins mr-2 text-yellow-400"></i>Credits Used (Last 30 Days)
        </h3>
        <canvas id="creditsChart" height="200"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Model Usage -->
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-brain mr-2 text-blue-400"></i>Top Models (Last 30 Days)
        </h3>
        @if(count($modelUsage) > 0)
        <div class="space-y-3">
            @foreach($modelUsage as $model)
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-300">{{ $model['name'] }}</span>
                        <span class="text-sm text-gray-400">{{ number_format($model['uses']) }} uses</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-500 to-blue-500 h-2 rounded-full"
                             style="width: {{ ($model['uses'] / max(1, $modelUsage[0]['uses'])) * 100 }}%"></div>
                    </div>
                </div>
                <span class="ml-4 text-sm text-yellow-400 min-w-[80px] text-right">{{ number_format($model['credits']) }} cr</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-400 text-center py-8">No model usage data yet</p>
        @endif
    </div>

    <!-- Top Users -->
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-trophy mr-2 text-yellow-400"></i>Top Users (Last 30 Days)
        </h3>
        @if(count($topUsers) > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase">
                        <th class="pb-2">#</th>
                        <th class="pb-2">User</th>
                        <th class="pb-2 text-right">Jobs</th>
                        <th class="pb-2 text-right">Credits</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($topUsers as $index => $user)
                    <tr class="hover:bg-gray-750">
                        <td class="py-2 text-gray-400">{{ $index + 1 }}</td>
                        <td class="py-2">
                            <p class="text-white">{{ $user['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $user['email'] }}</p>
                        </td>
                        <td class="py-2 text-right text-gray-300">{{ number_format($user['jobs']) }}</td>
                        <td class="py-2 text-right text-yellow-400">{{ number_format($user['credits']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-400 text-center py-8">No user data yet</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Prepare data
const dailyData = @json($dailyJobs);

// Jobs Chart
const jobsCtx = document.getElementById('jobsChart').getContext('2d');
new Chart(jobsCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Total Jobs',
            data: dailyData.map(d => d.total),
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: true,
            tension: 0.3
        }, {
            label: 'Completed',
            data: dailyData.map(d => d.completed),
            borderColor: '#10B981',
            backgroundColor: 'transparent',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: '#9CA3AF' }
            }
        },
        scales: {
            x: {
                ticks: { color: '#9CA3AF' },
                grid: { color: '#374151' }
            },
            y: {
                ticks: { color: '#9CA3AF' },
                grid: { color: '#374151' }
            }
        }
    }
});

// Credits Chart
const creditsCtx = document.getElementById('creditsChart').getContext('2d');
new Chart(creditsCtx, {
    type: 'bar',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Credits Used',
            data: dailyData.map(d => d.credits || 0),
            backgroundColor: 'rgba(245, 158, 11, 0.6)',
            borderColor: '#F59E0B',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: '#9CA3AF' }
            }
        },
        scales: {
            x: {
                ticks: { color: '#9CA3AF' },
                grid: { color: '#374151' }
            },
            y: {
                ticks: { color: '#9CA3AF' },
                grid: { color: '#374151' }
            }
        }
    }
});
</script>
@endpush
