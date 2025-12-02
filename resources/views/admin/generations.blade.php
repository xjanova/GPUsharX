@extends('layouts.admin')

@section('title', 'Generations Management')
@section('header', 'AI Generations Management')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total</p>
                <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <i class="fas fa-wand-magic-sparkles text-3xl text-purple-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Pending</p>
                <p class="text-2xl font-bold text-yellow-400">{{ $stats['pending'] }}</p>
            </div>
            <i class="fas fa-clock text-3xl text-yellow-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Processing</p>
                <p class="text-2xl font-bold text-blue-400">{{ $stats['processing'] }}</p>
            </div>
            <i class="fas fa-spinner text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Completed</p>
                <p class="text-2xl font-bold text-green-400">{{ $stats['completed'] }}</p>
            </div>
            <i class="fas fa-check-circle text-3xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Failed</p>
                <p class="text-2xl font-bold text-red-400">{{ $stats['failed'] }}</p>
            </div>
            <i class="fas fa-times-circle text-3xl text-red-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Credits Used</p>
                <p class="text-2xl font-bold text-emerald-400">{{ number_format($stats['total_credits'], 2) }}</p>
            </div>
            <i class="fas fa-coins text-3xl text-emerald-400"></i>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-gray-800 rounded-lg p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.generations') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Job ID or prompt..."
                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
        </div>
        <select name="status" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
        <select name="type" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            <option value="">All Types</option>
            <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Image</option>
            <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Video</option>
        </select>
        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
            <i class="fas fa-search mr-2"></i>Filter
        </button>
        <a href="{{ route('admin.generations') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
            <i class="fas fa-times mr-2"></i>Clear
        </a>
    </form>
</div>

<!-- Generations Table -->
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Job ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Prompt</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Credits</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Created</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($generations as $gen)
                <tr class="hover:bg-gray-750" id="gen-row-{{ $gen->id }}">
                    <td class="px-4 py-4">
                        <code class="text-xs text-purple-400 bg-gray-700 px-2 py-1 rounded">{{ $gen->job_id }}</code>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-white text-sm">{{ $gen->user->name ?? 'Unknown' }}</p>
                                <p class="text-gray-400 text-xs">{{ $gen->user->email ?? '-' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span class="text-gray-300 text-sm">{{ $gen->aiModel->name ?? 'Unknown Model' }}</span>
                    </td>
                    <td class="px-4 py-4">
                        <p class="text-gray-300 text-sm truncate max-w-[200px]" title="{{ $gen->prompt }}">{{ $gen->prompt }}</p>
                    </td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $gen->type === 'image' ? 'bg-blue-600/20 text-blue-400' : 'bg-green-600/20 text-green-400' }}">
                            {{ ucfirst($gen->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-600/20 text-yellow-400',
                                'processing' => 'bg-blue-600/20 text-blue-400',
                                'completed' => 'bg-green-600/20 text-green-400',
                                'failed' => 'bg-red-600/20 text-red-400',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$gen->status] ?? 'bg-gray-600/20 text-gray-400' }}">
                            {{ ucfirst($gen->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-300">{{ number_format($gen->credits_used, 2) }}</td>
                    <td class="px-4 py-4 text-gray-400 text-sm">{{ $gen->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @if($gen->result_url)
                            <a href="{{ $gen->result_url }}" target="_blank" class="text-blue-400 hover:text-blue-300 transition" title="View Result">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            @endif
                            <a href="{{ route('generate.status', $gen->job_id) }}" target="_blank" class="text-gray-400 hover:text-white transition" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="deleteGeneration({{ $gen->id }})" class="text-red-400 hover:text-red-300 transition" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                        No generation jobs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($generations->hasPages())
    <div class="px-4 py-3 border-t border-gray-700">
        {{ $generations->links() }}
    </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 border border-gray-700">
        <h3 class="text-xl font-semibold text-white mb-4">Confirm Delete</h3>
        <p class="text-gray-300 mb-6">Are you sure you want to delete this generation? This action cannot be undone.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                Cancel
            </button>
            <button id="confirm-delete-btn" onclick="confirmDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                Delete
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let deleteTargetId = null;

function deleteGeneration(id) {
    deleteTargetId = id;
    document.getElementById('delete-modal').classList.remove('hidden');
    document.getElementById('delete-modal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
    document.getElementById('delete-modal').classList.remove('flex');
    deleteTargetId = null;
}

async function confirmDelete() {
    if (!deleteTargetId) return;

    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i>Deleting...';

    try {
        const response = await fetch(`/admin/generations/${deleteTargetId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Remove row from table
            const row = document.getElementById('gen-row-' + deleteTargetId);
            if (row) {
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            closeDeleteModal();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Delete';
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Close modal on outside click
document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
@endpush
