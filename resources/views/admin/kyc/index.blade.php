@extends('layouts.admin')

@section('title', 'KYC Verification')
@section('header', 'จัดการ KYC')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">รอตรวจสอบ</p>
                <p class="text-2xl font-bold text-yellow-400">{{ number_format($counts['pending']) }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-hourglass-half text-xl text-yellow-400"></i>
            </div>
        </div>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">กำลังตรวจสอบ</p>
                <p class="text-2xl font-bold text-blue-400">{{ number_format($counts['reviewing']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-search text-xl text-blue-400"></i>
            </div>
        </div>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">อนุมัติแล้ว</p>
                <p class="text-2xl font-bold text-green-400">{{ number_format($counts['approved']) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-xl text-green-400"></i>
            </div>
        </div>
    </div>
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">ปฏิเสธ</p>
                <p class="text-2xl font-bold text-red-400">{{ number_format($counts['rejected']) }}</p>
            </div>
            <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-times-circle text-xl text-red-400"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="?status=all" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $status === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            ทั้งหมด <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-gray-600">{{ $counts['all'] }}</span>
        </a>
        <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $status === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            รอตรวจสอบ <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $status === 'pending' ? 'bg-yellow-700' : 'bg-yellow-500/30 text-yellow-400' }}">{{ $counts['pending'] }}</span>
        </a>
        <a href="?status=reviewing" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $status === 'reviewing' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            กำลังตรวจ <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $status === 'reviewing' ? 'bg-blue-700' : 'bg-blue-500/30 text-blue-400' }}">{{ $counts['reviewing'] }}</span>
        </a>
        <a href="?status=approved" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $status === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            อนุมัติ <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $status === 'approved' ? 'bg-green-700' : 'bg-green-500/30 text-green-400' }}">{{ $counts['approved'] }}</span>
        </a>
        <a href="?status=rejected" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $status === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
            ปฏิเสธ <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $status === 'rejected' ? 'bg-red-700' : 'bg-red-500/30 text-red-400' }}">{{ $counts['rejected'] }}</span>
        </a>
    </div>
</div>

<!-- KYC Table -->
<div class="bg-gray-800 rounded-xl border border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ID</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ผู้ใช้</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ชื่อ-นามสกุล</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ประเภทเอกสาร</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">วันที่ส่ง</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">สถานะ</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">ผู้ตรวจสอบ</th>
                    <th class="text-left px-6 py-4 text-sm text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($verifications as $kyc)
                <tr class="hover:bg-gray-700/50">
                    <td class="px-6 py-4 font-mono text-sm">#{{ $kyc->id }}</td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium">{{ $kyc->user->name }}</p>
                            <p class="text-sm text-gray-400">{{ $kyc->user->email }}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">{{ $kyc->full_name }}</td>
                    <td class="px-6 py-4 text-gray-400">
                        @php
                            $idTypes = [
                                'id_card' => 'บัตรประชาชน',
                                'passport' => 'หนังสือเดินทาง',
                                'driver_license' => 'ใบขับขี่'
                            ];
                        @endphp
                        {{ $idTypes[$kyc->id_type] ?? $kyc->id_type }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-400">
                            {{ $kyc->created_at->format('d/m/Y H:i') }}
                        </div>
                        @if($kyc->submission_count > 1)
                        <span class="text-xs text-blue-400">ส่งครั้งที่ {{ $kyc->submission_count }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($kyc->status === 'pending')
                            <span class="px-2 py-1 rounded-full text-xs bg-yellow-500/20 text-yellow-400">รอตรวจสอบ</span>
                        @elseif($kyc->status === 'reviewing')
                            <span class="px-2 py-1 rounded-full text-xs bg-blue-500/20 text-blue-400">กำลังตรวจสอบ</span>
                        @elseif($kyc->status === 'approved')
                            <span class="px-2 py-1 rounded-full text-xs bg-green-500/20 text-green-400">อนุมัติ</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-400">ปฏิเสธ</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        @if($kyc->reviewer)
                            <p>{{ $kyc->reviewer->name }}</p>
                            <p class="text-xs">{{ $kyc->reviewed_at?->format('d/m/Y H:i') }}</p>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.kyc.show', $kyc) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm transition">
                            <i class="fas fa-eye"></i>
                            <span>ดู</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>ไม่พบข้อมูล KYC</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($verifications->hasPages())
    <div class="px-6 py-4 border-t border-gray-700">
        {{ $verifications->appends(['status' => $status])->links() }}
    </div>
    @endif
</div>
@endsection
