@extends('layouts.admin')

@section('title', 'Node Detail')
@section('header', 'Node: ' . $node->node_id)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.nodes') }}" class="text-purple-400 hover:text-purple-300">
        <i class="fas fa-arrow-left mr-2"></i>กลับไปรายการ Nodes
    </a>
</div>

<!-- Node Info -->
<div class="grid md:grid-cols-2 gap-6 mb-6">
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-xl font-bold mb-4">ข้อมูล Node</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-400">Node ID</span>
                <span class="font-mono">{{ $node->node_id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">เจ้าของ</span>
                <a href="{{ route('admin.users.detail', $node->user) }}" class="text-purple-400 hover:text-purple-300">
                    {{ $node->user->name }}
                </a>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">GPU Model</span>
                <span>{{ $node->gpu_model }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">GPU Name</span>
                <span>{{ $node->gpu_name ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">VRAM</span>
                <span>{{ number_format($node->gpu_vram_mb / 1024, 1) }} GB</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Benchmark Score</span>
                <span>{{ number_format($node->benchmark_score) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Hashrate</span>
                <span>{{ number_format($node->hashrate, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Client Version</span>
                <span>{{ $node->client_version ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">IP Address</span>
                <span class="font-mono">{{ $node->ip_address ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">สถานะ</span>
                <span class="px-2 py-1 rounded-full text-xs
                    {{ $node->status === 'online' ? 'bg-green-500/20 text-green-400' : '' }}
                    {{ $node->status === 'working' ? 'bg-blue-500/20 text-blue-400' : '' }}
                    {{ $node->status === 'idle' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                    {{ $node->status === 'offline' ? 'bg-gray-500/20 text-gray-400' : '' }}
                    {{ $node->status === 'banned' ? 'bg-red-500/20 text-red-400' : '' }}">
                    {{ ucfirst($node->status) }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Last Heartbeat</span>
                <span>{{ $node->last_heartbeat ? $node->last_heartbeat->diffForHumans() : 'Never' }}</span>
            </div>
        </div>
    </div>

    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-xl font-bold mb-4">สถิติ</h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-4 bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold text-green-400">{{ number_format($stats['total_earnings'], 2) }}</p>
                <p class="text-gray-400 text-sm">รายได้รวม</p>
            </div>
            <div class="text-center p-4 bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold text-blue-400">{{ number_format($stats['total_completed']) }}</p>
                <p class="text-gray-400 text-sm">งานที่ทำเสร็จ</p>
            </div>
            <div class="text-center p-4 bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold text-purple-400">{{ number_format($stats['success_rate'] ?? 0, 1) }}%</p>
                <p class="text-gray-400 text-sm">Success Rate</p>
            </div>
            <div class="text-center p-4 bg-gray-700/50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-400">{{ number_format($stats['avg_completion_time'] ?? 0, 1) }}s</p>
                <p class="text-gray-400 text-sm">เวลาเฉลี่ย</p>
            </div>
        </div>

        <!-- Performance Info -->
        @if($node->performance_score)
        <div class="mt-4 pt-4 border-t border-gray-700">
            <h4 class="font-medium mb-3">Performance Metrics</h4>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Performance Score</span>
                    <span class="font-bold" style="color: {{ $node->rank_info['color'] }}">{{ number_format($node->performance_score, 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400">Rank</span>
                    <span class="px-2 py-1 rounded text-xs font-medium" style="background: {{ $node->rank_info['color'] }}20; color: {{ $node->rank_info['color'] }}">
                        <i class="fas fa-{{ $node->rank_info['icon'] }} mr-1"></i>{{ $node->rank_info['name'] }}
                    </span>
                </div>
                @if($node->speed_score)
                <div class="flex justify-between">
                    <span class="text-gray-400">Speed Score</span>
                    <span>{{ number_format($node->speed_score, 2) }}</span>
                </div>
                @endif
                @if($node->reliability_score)
                <div class="flex justify-between">
                    <span class="text-gray-400">Reliability Score</span>
                    <span>{{ number_format($node->reliability_score, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="mt-4 pt-4 border-t border-gray-700">
            <h4 class="font-medium mb-3">Actions</h4>
            <div class="flex gap-2">
                @if($node->status !== 'banned')
                <form action="{{ route('admin.nodes.ban', $node) }}" method="POST" onsubmit="return confirm('ยืนยันแบน Node?')">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-ban mr-1"></i>แบน
                    </button>
                </form>
                @else
                <form action="{{ route('admin.nodes.unban', $node) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-check mr-1"></i>ปลดแบน
                    </button>
                </form>
                @endif
                <form action="{{ route('admin.nodes.delete', $node) }}" method="POST" onsubmit="return confirm('ยืนยันลบ Node? การกระทำนี้ไม่สามารถยกเลิกได้!')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-800 hover:bg-red-900 px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-trash mr-1"></i>ลบ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Recent Jobs -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="p-6 border-b border-gray-700">
        <h3 class="text-xl font-bold">งานล่าสุด</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Chunk ID</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Job</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Status</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Credits</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Started</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Completed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentJobs as $chunk)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 font-mono text-sm">{{ $chunk->chunk_id }}</td>
                    <td class="px-6 py-4">
                        @if($chunk->renderJob)
                        {{ $chunk->renderJob->title ?? $chunk->renderJob->job_id }}
                        @else
                        -
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $chunk->status === 'completed' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $chunk->status === 'processing' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $chunk->status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $chunk->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}">
                            {{ ucfirst($chunk->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">{{ number_format($chunk->credits_earned ?? 0, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        {{ $chunk->started_at ? $chunk->started_at->diffForHumans() : '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        {{ $chunk->completed_at ? $chunk->completed_at->diffForHumans() : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">ไม่มีงานที่ทำ</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
