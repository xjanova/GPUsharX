<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarningsShowcase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_amount',
        'period',
        'screenshot',
        'message',
        'likes',
        'is_verified',
        'is_public',
    ];

    protected $casts = [
        'display_amount' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
