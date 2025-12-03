@extends('layouts.app')

@section('title', 'ยืนยันตัวตน (KYC)')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-5 fw-bold text-white mb-3">
                    <i class="bi bi-shield-check me-2"></i>ยืนยันตัวตน (KYC)
                </h1>
                <p class="text-white-50">การยืนยันตัวตนจำเป็นสำหรับการถอนเงินจากระบบ</p>
            </div>

            <!-- Status Card -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            @if($user->kyc_status === 'approved')
                                <div class="rounded-circle bg-success bg-opacity-25 p-3">
                                    <i class="bi bi-check-circle-fill text-success fs-1"></i>
                                </div>
                            @elseif($user->kyc_status === 'pending')
                                <div class="rounded-circle bg-warning bg-opacity-25 p-3">
                                    <i class="bi bi-hourglass-split text-warning fs-1"></i>
                                </div>
                            @elseif($user->kyc_status === 'rejected')
                                <div class="rounded-circle bg-danger bg-opacity-25 p-3">
                                    <i class="bi bi-x-circle-fill text-danger fs-1"></i>
                                </div>
                            @else
                                <div class="rounded-circle bg-secondary bg-opacity-25 p-3">
                                    <i class="bi bi-person-badge text-secondary fs-1"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col">
                            <h4 class="text-white mb-1">สถานะการยืนยัน</h4>
                            <p class="mb-0
                                @if($user->kyc_status === 'approved') text-success
                                @elseif($user->kyc_status === 'pending') text-warning
                                @elseif($user->kyc_status === 'rejected') text-danger
                                @else text-secondary
                                @endif">
                                {{ $user->kyc_status_label }}
                            </p>
                        </div>
                        <div class="col-auto">
                            @if($user->kyc_status === 'none' || $user->kyc_status === 'rejected')
                                <a href="{{ route('kyc.create') }}" class="btn btn-primary">
                                    <i class="bi bi-shield-plus me-1"></i>
                                    {{ $user->kyc_status === 'rejected' ? 'ส่งใหม่' : 'ยืนยันตัวตน' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- KYC Info (if exists) -->
            @if($kyc)
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-transparent border-secondary">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-person-vcard me-2"></i>ข้อมูลการยืนยันตัวตน
                    </h5>
                </div>
                <div class="card-body">
                    @if($kyc->isRejected() && $kyc->rejection_reason)
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>เหตุผลที่ไม่ผ่าน:</strong> {{ $kyc->rejection_reason }}
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">ชื่อ-นามสกุล</label>
                            <p class="text-white mb-0">{{ $kyc->full_name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">วันเกิด</label>
                            <p class="text-white mb-0">{{ $kyc->date_of_birth->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">ประเภทเอกสาร</label>
                            <p class="text-white mb-0">{{ \App\Models\KycVerification::getIdTypes()[$kyc->id_type] ?? $kyc->id_type }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">หมายเลขเอกสาร</label>
                            <p class="text-white mb-0">{{ $kyc->masked_id_number }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">เบอร์โทรศัพท์</label>
                            <p class="text-white mb-0">{{ $kyc->phone_number }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">จังหวัด</label>
                            <p class="text-white mb-0">{{ $kyc->province }}</p>
                        </div>
                    </div>

                    <hr class="border-secondary">

                    <h6 class="text-white mb-3">
                        <i class="bi bi-bank me-2"></i>บัญชีธนาคาร
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">ธนาคาร</label>
                            <p class="text-white mb-0">{{ $kyc->bank_display_name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-white-50 small">เลขบัญชี</label>
                            <p class="text-white mb-0">{{ $kyc->masked_account_number }}</p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="text-white-50 small">ชื่อบัญชี</label>
                            <p class="text-white mb-0">{{ $kyc->bank_account_name }}</p>
                        </div>
                    </div>

                    @if($kyc->isApproved())
                    <hr class="border-secondary">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-white-50 small">อนุมัติเมื่อ</label>
                            <p class="text-success mb-0">{{ $kyc->approved_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- History -->
            @if($kycHistory->count() > 1)
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-transparent border-secondary">
                    <h5 class="mb-0 text-white">
                        <i class="bi bi-clock-history me-2"></i>ประวัติการยืนยัน
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>วันที่ส่ง</th>
                                    <th>สถานะ</th>
                                    <th>หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kycHistory as $history)
                                <tr>
                                    <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($history->status === 'approved')
                                            <span class="badge bg-success">อนุมัติ</span>
                                        @elseif($history->status === 'pending')
                                            <span class="badge bg-warning">รอตรวจสอบ</span>
                                        @elseif($history->status === 'reviewing')
                                            <span class="badge bg-info">กำลังตรวจสอบ</span>
                                        @else
                                            <span class="badge bg-danger">ไม่ผ่าน</span>
                                        @endif
                                    </td>
                                    <td class="text-white-50">
                                        {{ $history->rejection_reason ?: '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Benefits Section -->
            <div class="card bg-dark border-secondary mt-4">
                <div class="card-body">
                    <h5 class="text-white mb-4">
                        <i class="bi bi-star me-2 text-warning"></i>ประโยชน์ของการยืนยันตัวตน
                    </h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span class="text-white-50">ถอนเงินได้ไม่จำกัดวงเงิน</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span class="text-white-50">รับโบนัสพิเศษสำหรับผู้ใช้ที่ยืนยันตัวตน</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span class="text-white-50">รับสิทธิ์เข้าร่วมโปรโมชั่นพิเศษ</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span class="text-white-50">เพิ่มความน่าเชื่อถือในระบบ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
