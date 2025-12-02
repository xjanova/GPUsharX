<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'fee',
        'balance_before',
        'balance_after',
        'status',
        'description',
        'metadata',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'fee' => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after' => 'decimal:8',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->transaction_id) {
                $model->transaction_id = 'TXN-' . strtoupper(Str::random(12));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'deposit', 'earning', 'referral', 'bonus', 'refund' => 'green',
            'withdrawal', 'fee' => 'red',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'deposit' => 'arrow-down',
            'withdrawal' => 'arrow-up',
            'earning' => 'coins',
            'referral' => 'users',
            'bonus' => 'gift',
            'fee' => 'percent',
            'refund' => 'rotate-left',
            default => 'exchange-alt',
        };
    }
}
