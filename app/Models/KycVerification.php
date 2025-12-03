<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'nationality',
        'id_type',
        'id_number',
        'phone_number',
        'address',
        'province',
        'district',
        'postal_code',
        'id_front_image',
        'id_back_image',
        'selfie_image',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'approved_at',
        'ip_address',
        'user_agent',
        'submission_count',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // ID Types
    const ID_TYPE_NATIONAL = 'national_id';
    const ID_TYPE_PASSPORT = 'passport';
    const ID_TYPE_DRIVING = 'driving_license';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Thai Banks
    const THAI_BANKS = [
        'BBL' => 'ธนาคารกรุงเทพ',
        'KBANK' => 'ธนาคารกสิกรไทย',
        'KTB' => 'ธนาคารกรุงไทย',
        'TTB' => 'ธนาคารทหารไทยธนชาต',
        'SCB' => 'ธนาคารไทยพาณิชย์',
        'BAY' => 'ธนาคารกรุงศรีอยุธยา',
        'KKP' => 'ธนาคารเกียรตินาคินภัทร',
        'CIMBT' => 'ธนาคารซีไอเอ็มบี ไทย',
        'TISCO' => 'ธนาคารทิสโก้',
        'UOBT' => 'ธนาคารยูโอบี',
        'LH' => 'ธนาคารแลนด์ แอนด์ เฮ้าส์',
        'ICBC' => 'ธนาคารไอซีบีซี (ไทย)',
        'GSB' => 'ธนาคารออมสิน',
        'BAAC' => 'ธนาคาร ธ.ก.ส.',
        'GHB' => 'ธนาคารอาคารสงเคราะห์',
    ];

    // Thai Provinces
    const THAI_PROVINCES = [
        'กรุงเทพมหานคร', 'กระบี่', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร',
        'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา', 'ชลบุรี', 'ชัยนาท',
        'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง',
        'ตราด', 'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม',
        'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส',
        'น่าน', 'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์',
        'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พังงา', 'พัทลุง',
        'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่',
        'พะเยา', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน',
        'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง',
        'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย',
        'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ',
        'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี',
        'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย',
        'หนองบัวลำภู', 'อ่างทอง', 'อุดรธานี', 'อุทัยธานี', 'อุตรดิตถ์',
        'อุบลราชธานี', 'อำนาจเจริญ',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->district}, {$this->province} {$this->postal_code}";
    }

    public function getBankDisplayNameAttribute(): string
    {
        return self::THAI_BANKS[$this->bank_name] ?? $this->bank_name;
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        $num = $this->bank_account_number;
        if (strlen($num) > 4) {
            return str_repeat('*', strlen($num) - 4) . substr($num, -4);
        }
        return $num;
    }

    public function getMaskedIdNumberAttribute(): string
    {
        $num = $this->id_number;
        if (strlen($num) > 4) {
            return substr($num, 0, 2) . str_repeat('*', strlen($num) - 4) . substr($num, -2);
        }
        return $num;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReviewing(): bool
    {
        return $this->status === self::STATUS_REVIEWING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(User $reviewer): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->user->update([
            'kyc_status' => 'approved',
            'withdrawal_limit' => 0, // No limit
        ]);
    }

    public function reject(User $reviewer, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->user->update([
            'kyc_status' => 'rejected',
        ]);
    }

    public function startReview(User $reviewer): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWING,
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public static function getIdTypes(): array
    {
        return [
            self::ID_TYPE_NATIONAL => 'บัตรประชาชน',
            self::ID_TYPE_PASSPORT => 'หนังสือเดินทาง',
            self::ID_TYPE_DRIVING => 'ใบขับขี่',
        ];
    }
}
