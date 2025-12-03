<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'purchase' => 'ซื้อเครดิต',
            'usage' => 'ใช้งาน',
            'refund' => 'คืนเครดิต',
            'admin_add' => 'แอดมินเพิ่ม',
            'admin_deduct' => 'แอดมินหัก',
            'admin_set' => 'แอดมินตั้งค่า',
            'referral_bonus' => 'โบนัสแนะนำ',
            'subscription' => 'สมัครสมาชิก',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'purchase', 'admin_add', 'refund', 'referral_bonus' => 'text-green-400',
            'usage', 'admin_deduct', 'subscription' => 'text-red-400',
            'admin_set' => 'text-blue-400',
            default => 'text-gray-400',
        };
    }
}
