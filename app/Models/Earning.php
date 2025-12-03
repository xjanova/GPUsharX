<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gpu_node_id',
        'job_chunk_id',
        'type',
        'amount',
        'platform_fee',
        'net_amount',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'platform_fee' => 'decimal:8',
        'net_amount' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gpuNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class);
    }

    public function jobChunk(): BelongsTo
    {
        return $this->belongsTo(JobChunk::class);
    }

    public static function createJobReward(
        User $user,
        GpuNode $node,
        JobChunk $chunk,
        float $amount,
        float $platformFeePercent = 10
    ): self {
        $platformFee = $amount * ($platformFeePercent / 100);
        $netAmount = $amount - $platformFee;

        $earning = self::create([
            'user_id' => $user->id,
            'gpu_node_id' => $node->id,
            'job_chunk_id' => $chunk->id,
            'type' => 'job_reward',
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
            'description' => "Reward for completing chunk {$chunk->chunk_id}",
            'status' => 'pending',
        ]);

        // Update user pending earnings
        $user->increment('pending_earnings', $netAmount);

        return $earning;
    }

    public function confirm(): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->update(['status' => 'confirmed']);

        // Move from pending to available balance
        $this->user->decrement('pending_earnings', $this->net_amount);
        $this->user->increment('balance', $this->net_amount);
        $this->user->increment('total_earned', $this->net_amount);

        // Distribute referral commissions to upline
        $this->distributeReferralCommissions();
    }

    /**
     * Distribute referral commissions to upline referrers
     */
    protected function distributeReferralCommissions(): void
    {
        try {
            $referralService = app(\App\Services\ReferralService::class);
            $referralService->distributeCommissions($this);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to distribute referral commissions', [
                'earning_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
