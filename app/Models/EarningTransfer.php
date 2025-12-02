<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarningTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'pending_before',
        'pending_after',
        'balance_before',
        'balance_after',
        'wallet_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'pending_before' => 'decimal:8',
        'pending_after' => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
