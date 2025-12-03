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
        <button type="button" onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i>เพิ่มผู้ใช้
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
                        <div class="flex gap-2">
                            <a href="{{ route('admin.users.detail', $user) }}" class="text-purple-400 hover:text-purple-300" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="openEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->role }}', '{{ $user->status }}')" class="text-blue-400 hover:text-blue-300" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openCreditModal({{ $user->id }}, '{{ $user->name }}', {{ $user->credits ?? 0 }})" class="text-yellow-400 hover:text-yellow-300" title="จัดการเครดิต">
                                <i class="fas fa-coins"></i>
                            </button>
                            @if($user->status !== 'banned')
                            <form action="{{ route('admin.users.ban', $user) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยันแบน {{ $user->name }}?')">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-300" title="แบน">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            @else
                            <form action="{{ route('admin.users.unban', $user) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยันปลดแบน {{ $user->name }}?')">
                                @csrf
                                <button type="submit" class="text-green-400 hover:text-green-300" title="ปลดแบน">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                            @endif
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.delete', $user) }}" method="POST" class="inline" onsubmit="return confirm('ยืนยันลบ {{ $user->name }}? การกระทำนี้ไม่สามารถยกเลิกได้!')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-400" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
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

<!-- Create User Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700">
        <h3 class="text-xl font-bold mb-4">เพิ่มผู้ใช้ใหม่</h3>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">ชื่อ</label>
                    <input type="text" name="name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">อีเมล</label>
                    <input type="email" name="email" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">รหัสผ่าน</label>
                    <input type="password" name="password" required minlength="8" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Role</label>
                    <select name="role" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="user">User</option>
                        <option value="moderator">Moderator</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">เครดิตเริ่มต้น</label>
                    <input type="number" name="credits" value="0" min="0" step="0.01" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg">สร้าง</button>
                <button type="button" onclick="closeCreateModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700">
        <h3 class="text-xl font-bold mb-4">แก้ไขผู้ใช้</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">ชื่อ</label>
                    <input type="text" name="name" id="edit_name" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">อีเมล</label>
                    <input type="email" name="email" id="edit_email" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">รหัสผ่านใหม่ (เว้นว่างถ้าไม่ต้องการเปลี่ยน)</label>
                    <input type="password" name="password" minlength="8" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Role</label>
                    <select name="role" id="edit_role" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="user">User</option>
                        <option value="moderator">Moderator</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">สถานะ</label>
                    <select name="status" id="edit_status" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="banned">Banned</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">บันทึก</button>
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<!-- Credit Management Modal -->
<div id="creditModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700">
        <h3 class="text-xl font-bold mb-2">จัดการเครดิต</h3>
        <p class="text-gray-400 mb-4">ผู้ใช้: <span id="credit_user_name" class="text-white"></span></p>
        <p class="text-gray-400 mb-4">เครดิตปัจจุบัน: <span id="credit_current" class="text-yellow-400 font-bold"></span></p>

        <div class="space-y-4">
            <!-- Add Credits -->
            <form id="addCreditForm" method="POST" class="border border-gray-700 rounded-lg p-4">
                @csrf
                <h4 class="font-medium text-green-400 mb-3"><i class="fas fa-plus-circle mr-2"></i>เพิ่มเครดิต</h4>
                <div class="flex gap-2 mb-2">
                    <input type="number" name="amount" placeholder="จำนวน" required min="0.01" step="0.01" class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <input type="text" name="reason" placeholder="เหตุผล" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white mb-2">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg">เพิ่มเครดิต</button>
            </form>

            <!-- Deduct Credits -->
            <form id="deductCreditForm" method="POST" class="border border-gray-700 rounded-lg p-4">
                @csrf
                <h4 class="font-medium text-red-400 mb-3"><i class="fas fa-minus-circle mr-2"></i>หักเครดิต</h4>
                <div class="flex gap-2 mb-2">
                    <input type="number" name="amount" placeholder="จำนวน" required min="0.01" step="0.01" class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <input type="text" name="reason" placeholder="เหตุผล" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white mb-2">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg">หักเครดิต</button>
            </form>

            <!-- Set Credits -->
            <form id="setCreditForm" method="POST" class="border border-gray-700 rounded-lg p-4">
                @csrf
                <h4 class="font-medium text-blue-400 mb-3"><i class="fas fa-edit mr-2"></i>ตั้งค่าเครดิต</h4>
                <div class="flex gap-2 mb-2">
                    <input type="number" name="amount" placeholder="จำนวน" required min="0" step="0.01" class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                </div>
                <input type="text" name="reason" placeholder="เหตุผล" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white mb-2">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">ตั้งค่าเครดิต</button>
            </form>
        </div>

        <button type="button" onclick="closeCreditModal()" class="w-full mt-4 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg">ปิด</button>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openEditModal(userId, name, email, role, status) {
    document.getElementById('editForm').action = `/admin/users/${userId}`;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function openCreditModal(userId, name, credits) {
    document.getElementById('credit_user_name').textContent = name;
    document.getElementById('credit_current').textContent = credits.toFixed(2);
    document.getElementById('addCreditForm').action = `/admin/users/${userId}/credits/add`;
    document.getElementById('deductCreditForm').action = `/admin/users/${userId}/credits/deduct`;
    document.getElementById('setCreditForm').action = `/admin/users/${userId}/credits/set`;
    document.getElementById('creditModal').classList.remove('hidden');
}

function closeCreditModal() {
    document.getElementById('creditModal').classList.add('hidden');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
        closeEditModal();
        closeCreditModal();
    }
});
</script>
@endsection
