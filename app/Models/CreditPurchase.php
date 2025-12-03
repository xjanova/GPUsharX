<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'credits_purchased',
        'bonus_credits',
        'amount_paid',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function getTotalCreditsAttribute(): int
    {
        return $this->credits_purchased + $this->bonus_credits;
    }

    public function complete(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->update(['status' => 'completed']);

        // Add credits to user
        $this->user->increment('credits', $this->total_credits);
        $this->user->increment('total_credits_purchased', $this->total_credits);
    }
}
