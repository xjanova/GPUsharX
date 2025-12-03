@extends('layouts.app')

@section('title', 'ยืนยันตัวตน - ส่งเอกสาร')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <a href="{{ route('kyc.index') }}" class="text-white-50 text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> กลับ
                </a>
            </div>

            <div class="text-center mb-5">
                <h1 class="display-6 fw-bold text-white mb-3">
                    <i class="bi bi-shield-check me-2"></i>ยืนยันตัวตน (KYC)
                </h1>
                <p class="text-white-50">กรุณากรอกข้อมูลและอัพโหลดเอกสารเพื่อยืนยันตัวตน</p>
            </div>

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('kyc.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Personal Information -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-transparent border-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-person me-2"></i>ข้อมูลส่วนตัว
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('first_name', $kyc?->first_name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('last_name', $kyc?->last_name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">วันเกิด <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('date_of_birth', $kyc?->date_of_birth?->format('Y-m-d')) }}" required>
                                <small class="text-white-50">คุณต้องมีอายุ 18 ปีขึ้นไป</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">สัญชาติ <span class="text-danger">*</span></label>
                                <input type="text" name="nationality" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('nationality', $kyc?->nationality ?? 'ไทย') }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ID Document -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-transparent border-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-card-heading me-2"></i>เอกสารยืนยันตัวตน
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">ประเภทเอกสาร <span class="text-danger">*</span></label>
                                <select name="id_type" class="form-select bg-secondary border-0 text-white" required>
                                    @foreach($idTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('id_type', $kyc?->id_type) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">หมายเลขเอกสาร <span class="text-danger">*</span></label>
                                <input type="text" name="id_number" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('id_number') }}" placeholder="เช่น 1-1234-56789-01-2" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">รูปบัตรด้านหน้า <span class="text-danger">*</span></label>
                                <input type="file" name="id_front_image" class="form-control bg-secondary border-0 text-white"
                                    accept="image/jpeg,image/png,image/jpg" required>
                                <small class="text-white-50">ไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">รูปบัตรด้านหลัง</label>
                                <input type="file" name="id_back_image" class="form-control bg-secondary border-0 text-white"
                                    accept="image/jpeg,image/png,image/jpg">
                                <small class="text-white-50">สำหรับบัตรประชาชน (ถ้ามี)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white">รูป Selfie ถือบัตร <span class="text-danger">*</span></label>
                            <input type="file" name="selfie_image" class="form-control bg-secondary border-0 text-white"
                                accept="image/jpeg,image/png,image/jpg" required>
                            <small class="text-white-50">ถ่ายรูปตัวเองถือบัตรให้เห็นหน้าและข้อมูลในบัตรชัดเจน</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>คำแนะนำการถ่ายรูป:</strong>
                            <ul class="mb-0 mt-2">
                                <li>ถ่ายในที่มีแสงสว่างเพียงพอ</li>
                                <li>ให้เห็นข้อมูลในบัตรชัดเจน ไม่เบลอ</li>
                                <li>ไม่ใช้รูปที่ถ่ายจากหน้าจอ</li>
                                <li>Selfie ต้องเห็นใบหน้าและบัตรในรูปเดียวกัน</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-transparent border-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-geo-alt me-2"></i>ข้อมูลติดต่อ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                <input type="tel" name="phone_number" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('phone_number', $kyc?->phone_number) }}" placeholder="0812345678" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                                <input type="text" name="postal_code" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('postal_code', $kyc?->postal_code) }}" placeholder="10110" maxlength="5" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white">ที่อยู่ <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control bg-secondary border-0 text-white" rows="2"
                                placeholder="บ้านเลขที่ ถนน ซอย" required>{{ old('address', $kyc?->address) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">แขวง/ตำบล <span class="text-danger">*</span></label>
                                <input type="text" name="district" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('district', $kyc?->district) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">จังหวัด <span class="text-danger">*</span></label>
                                <select name="province" class="form-select bg-secondary border-0 text-white" required>
                                    <option value="">-- เลือกจังหวัด --</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}" {{ old('province', $kyc?->province) === $province ? 'selected' : '' }}>
                                            {{ $province }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Account -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-transparent border-secondary">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-bank me-2"></i>บัญชีธนาคารสำหรับรับเงิน
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">ธนาคาร <span class="text-danger">*</span></label>
                                <select name="bank_name" class="form-select bg-secondary border-0 text-white" required>
                                    <option value="">-- เลือกธนาคาร --</option>
                                    @foreach($banks as $code => $name)
                                        <option value="{{ $code }}" {{ old('bank_name', $kyc?->bank_name) === $code ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-white">เลขบัญชี <span class="text-danger">*</span></label>
                                <input type="text" name="bank_account_number" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('bank_account_number') }}" placeholder="1234567890" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label text-white">ชื่อบัญชี <span class="text-danger">*</span></label>
                                <input type="text" name="bank_account_name" class="form-control bg-secondary border-0 text-white"
                                    value="{{ old('bank_account_name', $kyc?->bank_account_name) }}"
                                    placeholder="ชื่อ-นามสกุล ตามบัญชีธนาคาร" required>
                                <small class="text-white-50">ต้องตรงกับชื่อในเอกสารยืนยันตัวตน</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree_terms" required>
                            <label class="form-check-label text-white" for="agree_terms">
                                ข้าพเจ้ายืนยันว่าข้อมูลทั้งหมดเป็นความจริง และยินยอมให้เก็บข้อมูลเพื่อการยืนยันตัวตน
                                ตาม <a href="#" class="text-primary">นโยบายความเป็นส่วนตัว</a>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send me-2"></i>ส่งข้อมูลยืนยันตัวตน
                    </button>
                    <a href="{{ route('kyc.index') }}" class="btn btn-outline-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
