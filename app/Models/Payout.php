<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payout_id',
        'amount',
        'payment_method',
        'payment_details',
        'status',
        'transaction_id',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'payment_details' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payout) {
            if (empty($payout->payout_id)) {
                $payout->payout_id = 'PAY-' . strtoupper(Str::random(12));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function requestPayout(User $user, float $amount, string $method, array $details): self
    {
        if ($user->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $payout = self::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_details' => $details,
            'status' => 'pending',
        ]);

        // Deduct from balance
        $user->decrement('balance', $amount);

        return $payout;
    }

    public function process(): void
    {
        $this->update([
            'status' => 'processing',
        ]);
    }

    public function complete(string $transactionId): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'processed_at' => now(),
        ]);

        $this->user->increment('total_withdrawn', $this->amount);
    }

    public function fail(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);

        // Refund to user balance
        $this->user->increment('balance', $this->amount);
    }
}
