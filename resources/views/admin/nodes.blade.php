@extends('layouts.admin')

@section('title', 'GPU Nodes')
@section('header', 'GPU Node Management')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold">{{ $nodeStats['total'] }}</p>
        <p class="text-gray-400 text-sm">Total</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-green-400">{{ $nodeStats['online'] }}</p>
        <p class="text-gray-400 text-sm">Online</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-blue-400">{{ $nodeStats['working'] }}</p>
        <p class="text-gray-400 text-sm">Working</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-yellow-400">{{ $nodeStats['idle'] }}</p>
        <p class="text-gray-400 text-sm">Idle</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-gray-400">{{ $nodeStats['offline'] }}</p>
        <p class="text-gray-400 text-sm">Offline</p>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 text-center">
        <p class="text-2xl font-bold text-red-400">{{ $nodeStats['banned'] }}</p>
        <p class="text-gray-400 text-sm">Banned</p>
    </div>
</div>

<!-- Search and Filters -->
<div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.nodes') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm text-gray-400 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Node ID or GPU model..."
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Status</label>
            <select name="status" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                <option value="">All Status</option>
                <option value="online" {{ request('status') === 'online' ? 'selected' : '' }}>Online</option>
                <option value="working" {{ request('status') === 'working' ? 'selected' : '' }}>Working</option>
                <option value="idle" {{ request('status') === 'idle' ? 'selected' : '' }}>Idle</option>
                <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg">
            <i class="fas fa-search mr-2"></i>Search
        </button>
    </form>
</div>

<!-- Nodes Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Node ID</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Owner</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">GPU Model</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">VRAM</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Hashrate</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Benchmark</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Status</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Last Seen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($nodes as $node)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 font-mono text-sm">{{ $node->node_id }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.users.detail', $node->user) }}" class="text-purple-400 hover:text-purple-300">
                            {{ $node->user->name }}
                        </a>
                    </td>
                    <td class="px-6 py-4">{{ $node->gpu_model }}</td>
                    <td class="px-6 py-4">{{ number_format($node->gpu_vram_mb / 1024, 1) }} GB</td>
                    <td class="px-6 py-4">{{ number_format($node->hashrate, 2) }}</td>
                    <td class="px-6 py-4">{{ number_format($node->benchmark_score) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $node->status === 'online' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $node->status === 'working' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $node->status === 'idle' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $node->status === 'offline' ? 'bg-gray-500/20 text-gray-400' : '' }}
                            {{ $node->status === 'banned' ? 'bg-red-500/20 text-red-400' : '' }}">
                            {{ ucfirst($node->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        {{ $node->last_heartbeat ? $node->last_heartbeat->diffForHumans() : 'Never' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">No nodes found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($nodes->hasPages())
    <div class="px-6 py-4 border-t border-gray-700">
        {{ $nodes->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
