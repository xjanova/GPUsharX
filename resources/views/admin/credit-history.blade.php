@extends('layouts.admin')

@section('title', 'Credit History')
@section('header', 'ประวัติการใช้เครดิต')

@section('content')
<!-- Filters -->
<div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.credit-history') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-400 mb-1">ประเภท</label>
            <select name="type" class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white">
                <option value="">ทั้งหมด</option>
                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>ซื้อเครดิต</option>
                <option value="usage" {{ request('type') === 'usage' ? 'selected' : '' }}>ใช้งาน</option>
                <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>คืนเครดิต</option>
                <option value="admin_add" {{ request('type') === 'admin_add' ? 'selected' : '' }}>แอดมินเพิ่ม</option>
                <option value="admin_deduct" {{ request('type') === 'admin_deduct' ? 'selected' : '' }}>แอดมินหัก</option>
                <option value="admin_set" {{ request('type') === 'admin_set' ? 'selected' : '' }}>แอดมินตั้งค่า</option>
                <option value="referral_bonus" {{ request('type') === 'referral_bonus' ? 'selected' : '' }}>โบนัสแนะนำ</option>
            </select>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg">
            <i class="fas fa-filter mr-2"></i>กรอง
        </button>
        @if(request()->hasAny(['type', 'user_id']))
        <a href="{{ route('admin.credit-history') }}" class="bg-gray-600 hover:bg-gray-700 px-6 py-2 rounded-lg">
            <i class="fas fa-times mr-2"></i>ล้างตัวกรอง
        </a>
        @endif
    </form>
</div>

<!-- Transactions Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">วันที่</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ผู้ใช้</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ประเภท</th>
                    <th class="text-right px-6 py-4 text-sm text-gray-400">จำนวน</th>
                    <th class="text-right px-6 py-4 text-sm text-gray-400">ยอดคงเหลือ</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">รายละเอียด</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ดำเนินการโดย</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($transactions as $tx)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 text-sm text-gray-400">
                        {{ $tx->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($tx->user)
                        <a href="{{ route('admin.users.detail', $tx->user) }}" class="text-purple-400 hover:text-purple-300">
                            {{ $tx->user->name }}
                        </a>
                        @else
                        <span class="text-gray-500">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded text-xs {{ $tx->type_color }}">
                            {{ $tx->type_name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right font-mono {{ $tx->amount >= 0 ? 'text-green-400' : 'text-red-400' }}">
                        {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                    </td>
                    <td class="px-6 py-4 text-right font-mono">
                        {{ number_format($tx->balance_after, 2) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        {{ Str::limit($tx->description, 50) }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($tx->creator)
                        <span class="text-gray-400">{{ $tx->creator->name }}</span>
                        @else
                        <span class="text-gray-500">ระบบ</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">ไม่มีประวัติการทำรายการ</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
    <div class="px-6 py-4 border-t border-gray-700">
        {{ $transactions->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
