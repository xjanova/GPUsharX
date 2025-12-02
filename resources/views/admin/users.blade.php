@extends('layouts.admin')

@section('title', 'Users')
@section('header', 'User Management')

@section('content')
<!-- Search and Filters -->
<div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.users') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm text-gray-400 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, or referral code..."
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Status</label>
            <select name="status" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg">
            <i class="fas fa-search mr-2"></i>Search
        </button>
    </form>
</div>

<!-- Users Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">User</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Referral Code</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Nodes</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Balance</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Total Earned</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Status</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Joined</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($users as $user)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-sm text-gray-400">{{ $user->email }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm">{{ $user->referral_code }}</td>
                    <td class="px-6 py-4">{{ $user->gpu_nodes_count }}</td>
                    <td class="px-6 py-4">${{ number_format($user->balance, 2) }}</td>
                    <td class="px-6 py-4">${{ number_format($user->total_earned, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $user->status === 'active' ? 'bg-green-500/20 text-green-400' : '' }}
                            {{ $user->status === 'suspended' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                            {{ $user->status === 'banned' ? 'bg-red-500/20 text-red-400' : '' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.users.detail', $user) }}" class="text-purple-400 hover:text-purple-300 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">No users found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-700">
        {{ $users->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
