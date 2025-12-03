@extends('layouts.admin')

@section('title', 'Manual Payments')
@section('header', 'จัดการการชำระเงินแบบ Manual')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">การชำระเงินแบบ Manual</h1>
            <p class="text-gray-400 mt-1">ตรวจสอบและอนุมัติการชำระเงินจากผู้ใช้</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-yellow-400">รอตรวจสอบ</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-green-400">อนุมัติแล้ว</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['approved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-red-400">ถูกปฏิเสธ</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['rejected'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-blue-400">ยอดรอตรวจสอบ</p>
                    <p class="text-2xl font-bold text-white">฿{{ number_format($stats['pending_amount'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-800/50 backdrop-blur rounded-xl border border-gray-700/50 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500"
                       placeholder="ค้นหาชื่อหรืออีเมล...">
            </div>
            <div>
                <select name="status"
                        class="bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ถูกปฏิเสธ</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">
                ค้นหา
            </button>
            @if(request('search') || request('status'))
            <a href="{{ route('admin.manual-payments') }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                ล้าง
            </a>
            @endif
        </form>
    </div>

    @if(session('success'))
    <div class="p-4 bg-green-500/10 border border-green-500/30 rounded-xl">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-green-400">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-red-400">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <!-- Payments List -->
    <div class="bg-gray-800/50 backdrop-blur rounded-xl border border-gray-700/50 overflow-hidden">
        @if($payments->isEmpty())
        <div class="p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-400 text-lg">ไม่พบรายการการชำระเงิน</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">แพ็คเกจ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ยอดโอน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">วันที่โอน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">สลิป</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($payments as $payment)
                    <tr class="hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($payment->user->name, 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-white font-medium">{{ $payment->user->name }}</p>
                                    <p class="text-gray-400 text-sm">{{ $payment->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-white">{{ config("stripe.packages.{$payment->package}.name", $payment->package) }}</p>
                            <p class="text-gray-400 text-sm">{{ number_format($payment->credits) }} เครดิต</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-green-400 font-medium">฿{{ number_format($payment->amount, 2) }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-white">{{ $payment->transfer_date->format('d/m/Y') }}</p>
                            <p class="text-gray-400 text-sm">{{ $payment->transfer_time }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ $payment->slip_url }}" target="_blank" class="block">
                                <img src="{{ $payment->slip_url }}"
                                     alt="Slip"
                                     class="w-12 h-12 object-cover rounded-lg border border-gray-600 hover:border-blue-500 transition-colors">
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->isPending())
                            <span class="px-3 py-1 bg-yellow-500/10 text-yellow-400 text-sm rounded-full">รอตรวจสอบ</span>
                            @elseif($payment->isApproved())
                            <span class="px-3 py-1 bg-green-500/10 text-green-400 text-sm rounded-full">อนุมัติแล้ว</span>
                            @else
                            <span class="px-3 py-1 bg-red-500/10 text-red-400 text-sm rounded-full">ถูกปฏิเสธ</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if($payment->isPending())
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.manual-payments.approve', $payment) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('ยืนยันอนุมัติการชำระเงินนี้?')"
                                            class="px-3 py-1.5 bg-green-600 hover:bg-green-500 text-white text-sm rounded-lg transition-colors">
                                        อนุมัติ
                                    </button>
                                </form>
                                <button onclick="openRejectModal({{ $payment->id }})"
                                        class="px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white text-sm rounded-lg transition-colors">
                                    ปฏิเสธ
                                </button>
                            </div>
                            @else
                            <span class="text-gray-500 text-sm">{{ $payment->processed_at?->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-700/50">
            {{ $payments->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-2xl border border-gray-700 p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold text-white mb-4">ปฏิเสธการชำระเงิน</h3>
            <form id="reject-form" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">เหตุผลที่ปฏิเสธ</label>
                    <textarea name="reason" rows="3" required
                              class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="กรุณาระบุเหตุผล..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()"
                            class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition-colors">
                        ยืนยันปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRejectModal(paymentId) {
    const modal = document.getElementById('reject-modal');
    const form = document.getElementById('reject-form');
    form.action = `/admin/manual-payments/${paymentId}/reject`;
    modal.classList.remove('hidden');
}

function closeRejectModal() {
    const modal = document.getElementById('reject-modal');
    modal.classList.add('hidden');
}

// Close modal on backdrop click
document.getElementById('reject-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>
@endsection
