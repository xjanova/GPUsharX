@extends('layouts.admin')

@section('title', 'ตรวจสอบ KYC')
@section('header', 'ตรวจสอบ KYC - ' . $kyc->full_name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.kyc.index') }}" class="text-purple-400 hover:text-purple-300">
        <i class="fas fa-arrow-left mr-2"></i>กลับไปรายการ KYC
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Status Banner -->
        <div class="rounded-xl p-4 border
            @if($kyc->status === 'approved') bg-green-500/20 border-green-500/30
            @elseif($kyc->status === 'pending' || $kyc->status === 'reviewing') bg-yellow-500/20 border-yellow-500/30
            @else bg-red-500/20 border-red-500/30
            @endif">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($kyc->status === 'pending')
                        <i class="fas fa-hourglass-half text-2xl text-yellow-400"></i>
                        <div>
                            <p class="font-semibold text-yellow-400">รอตรวจสอบ</p>
                            <p class="text-sm text-gray-400">KYC นี้รอการตรวจสอบจากแอดมิน</p>
                        </div>
                    @elseif($kyc->status === 'reviewing')
                        <i class="fas fa-search text-2xl text-blue-400"></i>
                        <div>
                            <p class="font-semibold text-blue-400">กำลังตรวจสอบ</p>
                            <p class="text-sm text-gray-400">KYC นี้กำลังอยู่ระหว่างการตรวจสอบ</p>
                        </div>
                    @elseif($kyc->status === 'approved')
                        <i class="fas fa-check-circle text-2xl text-green-400"></i>
                        <div>
                            <p class="font-semibold text-green-400">อนุมัติแล้ว</p>
                            <p class="text-sm text-gray-400">KYC นี้ได้รับการอนุมัติเรียบร้อยแล้ว</p>
                        </div>
                    @else
                        <i class="fas fa-times-circle text-2xl text-red-400"></i>
                        <div>
                            <p class="font-semibold text-red-400">ปฏิเสธ</p>
                            <p class="text-sm text-gray-400">KYC นี้ถูกปฏิเสธ</p>
                        </div>
                    @endif
                </div>
                @if($kyc->submission_count > 1)
                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-sm">
                    ส่งครั้งที่ {{ $kyc->submission_count }}
                </span>
                @endif
            </div>
            @if($kyc->rejection_reason)
            <div class="mt-4 pt-4 border-t border-red-500/30">
                <p class="text-sm text-gray-400">เหตุผลที่ปฏิเสธ:</p>
                <p class="text-red-400">{{ $kyc->rejection_reason }}</p>
            </div>
            @endif
        </div>

        <!-- Personal Info -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-user text-purple-400"></i>
                    ข้อมูลส่วนตัว
                </h3>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-gray-400 text-sm">ชื่อ-นามสกุล</label>
                        <p class="text-lg font-medium">{{ $kyc->full_name }}</p>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">วันเกิด</label>
                        <p>{{ $kyc->date_of_birth->format('d/m/Y') }}
                            <span class="text-gray-400">(อายุ {{ $kyc->date_of_birth->age }} ปี)</span>
                        </p>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">สัญชาติ</label>
                        <p>{{ $kyc->nationality }}</p>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">เบอร์โทรศัพท์</label>
                        <p>{{ $kyc->phone_number }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-gray-400 text-sm">ที่อยู่</label>
                        <p>{{ $kyc->full_address }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ID Document -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-id-card text-purple-400"></i>
                    เอกสารยืนยันตัวตน
                </h3>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="text-gray-400 text-sm">ประเภทเอกสาร</label>
                        @php
                            $idTypes = [
                                'id_card' => 'บัตรประชาชน',
                                'passport' => 'หนังสือเดินทาง',
                                'driver_license' => 'ใบขับขี่'
                            ];
                        @endphp
                        <p>{{ $idTypes[$kyc->id_type] ?? $kyc->id_type }}</p>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">หมายเลขเอกสาร</label>
                        <p class="font-mono">{{ $kyc->id_number }}</p>
                    </div>
                </div>

                <!-- Document Images -->
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-gray-400 text-sm block mb-2">รูปบัตรด้านหน้า</label>
                        <a href="{{ route('admin.kyc.document', [$kyc, 'id_front']) }}" target="_blank" class="block">
                            <img src="{{ route('admin.kyc.document', [$kyc, 'id_front']) }}"
                                class="w-full h-40 object-contain bg-gray-900 rounded-lg border border-gray-600 hover:border-purple-500 transition" alt="ID Front">
                        </a>
                    </div>
                    @if($kyc->id_back_image)
                    <div>
                        <label class="text-gray-400 text-sm block mb-2">รูปบัตรด้านหลัง</label>
                        <a href="{{ route('admin.kyc.document', [$kyc, 'id_back']) }}" target="_blank" class="block">
                            <img src="{{ route('admin.kyc.document', [$kyc, 'id_back']) }}"
                                class="w-full h-40 object-contain bg-gray-900 rounded-lg border border-gray-600 hover:border-purple-500 transition" alt="ID Back">
                        </a>
                    </div>
                    @endif
                    <div>
                        <label class="text-gray-400 text-sm block mb-2">Selfie ถือบัตร</label>
                        <a href="{{ route('admin.kyc.document', [$kyc, 'selfie']) }}" target="_blank" class="block">
                            <img src="{{ route('admin.kyc.document', [$kyc, 'selfie']) }}"
                                class="w-full h-40 object-contain bg-gray-900 rounded-lg border border-gray-600 hover:border-purple-500 transition" alt="Selfie">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Account -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-university text-purple-400"></i>
                    บัญชีธนาคาร
                </h3>
            </div>
            <div class="p-6">
                <div class="grid md:grid-cols-3 gap-6 mb-4">
                    <div>
                        <label class="text-gray-400 text-sm">ธนาคาร</label>
                        <p>{{ $kyc->bank_display_name }}</p>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">เลขบัญชี</label>
                        <p class="font-mono">{{ $kyc->bank_account_number }}</p>
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">ชื่อบัญชี</label>
                        <p>{{ $kyc->bank_account_name }}</p>
                    </div>
                </div>

                <!-- Verification Check -->
                <div class="p-4 rounded-lg {{ $kyc->full_name === $kyc->bank_account_name ? 'bg-green-500/20 border border-green-500/30' : 'bg-yellow-500/20 border border-yellow-500/30' }}">
                    @if($kyc->full_name === $kyc->bank_account_name)
                        <div class="flex items-center gap-2 text-green-400">
                            <i class="fas fa-check-circle"></i>
                            <span>ชื่อบัญชีตรงกับชื่อในเอกสาร</span>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-yellow-400 mb-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="font-semibold">ชื่อบัญชีไม่ตรงกับชื่อในเอกสาร</span>
                        </div>
                        <div class="text-sm text-gray-300 space-y-1">
                            <p>เอกสาร: <span class="text-white">{{ $kyc->full_name }}</span></p>
                            <p>บัญชี: <span class="text-white">{{ $kyc->bank_account_name }}</span></p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- User Info -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-user-circle text-purple-400"></i>
                    ข้อมูลผู้ใช้
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="text-gray-400 text-sm">ชื่อผู้ใช้</label>
                    <p>{{ $kyc->user->name }}</p>
                </div>
                <div>
                    <label class="text-gray-400 text-sm">อีเมล</label>
                    <p>{{ $kyc->user->email }}</p>
                </div>
                <div>
                    <label class="text-gray-400 text-sm">สมัครเมื่อ</label>
                    <p>{{ $kyc->user->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-gray-400 text-sm">ยอดเงินในบัญชี</label>
                    <p class="text-xl font-bold text-green-400">฿{{ number_format($kyc->user->balance, 2) }}</p>
                </div>
                <div class="pt-4 border-t border-gray-700">
                    <a href="{{ route('admin.users.detail', $kyc->user) }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition">
                        <i class="fas fa-user"></i>
                        <span>ดูโปรไฟล์ผู้ใช้</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-info-circle text-purple-400"></i>
                    ข้อมูลการส่ง
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="text-gray-400 text-sm">วันที่ส่ง</label>
                    <p>{{ $kyc->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <label class="text-gray-400 text-sm">IP Address</label>
                    <p class="font-mono text-sm">{{ $kyc->ip_address }}</p>
                </div>
                @if($kyc->reviewer)
                <div>
                    <label class="text-gray-400 text-sm">ผู้ตรวจสอบ</label>
                    <p>{{ $kyc->reviewer->name }}</p>
                </div>
                <div>
                    <label class="text-gray-400 text-sm">ตรวจสอบเมื่อ</label>
                    <p>{{ $kyc->reviewed_at?->format('d/m/Y H:i') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if($kyc->status === 'pending' || $kyc->status === 'reviewing')
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-tasks text-purple-400"></i>
                    ดำเนินการ
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <form action="{{ route('admin.kyc.approve', $kyc) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition flex items-center justify-center gap-2"
                        onclick="return confirm('ยืนยันอนุมัติ KYC นี้?')">
                        <i class="fas fa-check-circle"></i>
                        <span>อนุมัติ</span>
                    </button>
                </form>

                <div class="border-t border-gray-700 pt-4">
                    <form action="{{ route('admin.kyc.reject', $kyc) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="text-sm text-gray-400 mb-2 block">เหตุผลที่ปฏิเสธ</label>
                            <textarea name="rejection_reason" rows="3" required
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="ระบุเหตุผล..."></textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition flex items-center justify-center gap-2"
                            onclick="return confirm('ยืนยันปฏิเสธ KYC นี้?')">
                            <i class="fas fa-times-circle"></i>
                            <span>ปฏิเสธ</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
