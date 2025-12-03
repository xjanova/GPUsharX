@extends('layouts.admin')

@section('title', 'Jobs')
@section('header', 'Job Management')

@section('content')
<!-- Stats and Actions -->
<div class="flex flex-wrap justify-between items-center mb-6 gap-4">
    <div class="flex gap-4">
        <div class="bg-gray-800 rounded-xl px-4 py-2 border border-gray-700">
            <span class="text-yellow-400 font-bold">{{ $jobStats['pending'] + $jobStats['queued'] }}</span>
            <span class="text-gray-400 text-sm ml-1">Pending</span>
        </div>
        <div class="bg-gray-800 rounded-xl px-4 py-2 border border-gray-700">
            <span class="text-blue-400 font-bold">{{ $jobStats['processing'] }}</span>
            <span class="text-gray-400 text-sm ml-1">Processing</span>
        </div>
        <div class="bg-gray-800 rounded-xl px-4 py-2 border border-gray-700">
            <span class="text-green-400 font-bold">{{ $jobStats['completed'] }}</span>
            <span class="text-gray-400 text-sm ml-1">Completed</span>
        </div>
        <div class="bg-gray-800 rounded-xl px-4 py-2 border border-gray-700">
            <span class="text-red-400 font-bold">{{ $jobStats['failed'] }}</span>
            <span class="text-gray-400 text-sm ml-1">Failed</span>
        </div>
    </div>
    <a href="{{ route('admin.jobs.create') }}" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg">
        <i class="fas fa-plus mr-2"></i>Create Job
    </a>
</div>

<!-- Filters -->
<div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.jobs') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Status</label>
            <select name="status" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Type</label>
            <select name="type" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                <option value="">All Types</option>
                <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Image</option>
                <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Video</option>
                <option value="animation" {{ request('type') === 'animation' ? 'selected' : '' }}>Animation</option>
                <option value="3d_render" {{ request('type') === '3d_render' ? 'selected' : '' }}>3D Render</option>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Jobs Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Job ID</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Title</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Type</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Priority</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Progress</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Credits</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Status</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Created</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($jobs as $job)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 font-mono text-sm">{{ $job->job_id }}</td>
                    <td class="px-6 py-4">{{ Str::limit($job->title, 30) }}</td>
                    <td class="px-6 py-4">
                        <span class="capitalize">{{ str_replace('_', ' ', $job->type) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $job->priority === 'urgent' ? 'bg-red-500/20 text-red-400' : '' }}
                            {{ $job->priority === 'high' ? 'bg-orange-500/20 text-orange-400' : '' }}
                            {{ $job->priority === 'normal' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ $job->priority === 'low' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                            {{ ucfirst($job->priority) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-20 bg-gray-700 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $job->progress_percentage }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $job->completed_chunks }}/{{ $job->total_chunks }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ number_format($job->estimated_credits) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $job->status === 'completed' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $job->status === 'processing' ? 'bg-blue-500/20 text-blue-400' : '' }}
                            {{ in_array($job->status, ['pending', 'queued']) ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $job->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}
                            {{ $job->status === 'cancelled' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                            {{ ucfirst($job->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">{{ $job->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            @if(!in_array($job->status, ['completed', 'cancelled']))
                            <form action="{{ route('admin.jobs.cancel', $job) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยันยกเลิกงานและคืนเครดิต?')">
                                @csrf
                                <button type="submit" class="text-yellow-400 hover:text-yellow-300 text-sm" title="ยกเลิกและคืนเครดิต">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.jobs.force-fail', $job) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยัน force fail งานและคืนเครดิต?')">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-300 text-sm" title="Force Fail และคืนเครดิต">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </form>
                            @endif
                            @if(in_array($job->status, ['failed', 'cancelled']))
                            <form action="{{ route('admin.jobs.retry', $job) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยัน retry งาน?')">
                                @csrf
                                <button type="submit" class="text-green-400 hover:text-green-300 text-sm" title="Retry งาน">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">No jobs found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($jobs->hasPages())
    <div class="px-6 py-4 border-t border-gray-700">
        {{ $jobs->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
