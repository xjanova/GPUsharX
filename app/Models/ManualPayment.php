<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPayment extends Model
{
    protected $fillable = [
        'user_id',
        'package',
        'amount',
        'credits',
        'transfer_date',
        'transfer_time',
        'slip_path',
        'note',
        'status',
        'reject_reason',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getSlipUrlAttribute(): string
    {
        return asset('storage/' . $this->slip_path);
    }
}
