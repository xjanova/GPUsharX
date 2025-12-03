<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'referral_code',
        'referred_by',
        'balance',
        'credits',
        'total_earned',
        'total_withdrawn',
        'pending_earnings',
        'status',
        'payment_info',
        'last_activity',
        // Google OAuth
        'google_id',
        'google_email',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'google_drive_folder_id',
        // KYC
        'kyc_status',
        'withdrawal_limit',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:8',
            'credits' => 'decimal:2',
            'total_earned' => 'decimal:8',
            'total_withdrawn' => 'decimal:8',
            'pending_earnings' => 'decimal:8',
            'payment_info' => 'array',
            'last_activity' => 'datetime',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Check if Google Drive is connected and token is valid
     */
    public function getGoogleDriveConnectedAttribute(): bool
    {
        if (!$this->google_access_token || !$this->google_refresh_token) {
            return false;
        }

        // Token ยังไม่หมดอายุ หรือมี refresh token
        return $this->google_refresh_token !== null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = strtoupper(Str::random(8));
            }
        });
    }

    public function gpuNodes(): HasMany
    {
        return $this->hasMany(GpuNode::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(ReferralTree::class, 'referrer_id');
    }

    public function directReferrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referralEarnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class);
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function createdJobs(): HasMany
    {
        return $this->hasMany(RenderJob::class, 'created_by');
    }

    public function generationJobs(): HasMany
    {
        return $this->hasMany(GenerationJob::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest();
    }

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class);
    }

    public function kycVerification()
    {
        return $this->hasOne(KycVerification::class)->latest();
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getTotalHashrateAttribute(): float
    {
        return $this->gpuNodes()->online()->sum('hashrate');
    }

    public function getActiveNodesCountAttribute(): int
    {
        return $this->gpuNodes()->online()->count();
    }

    public function updateActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }

    public function isKycApproved(): bool
    {
        return $this->kyc_status === 'approved';
    }

    public function isKycPending(): bool
    {
        return $this->kyc_status === 'pending';
    }

    public function canWithdraw(): bool
    {
        return $this->isActive() && $this->isKycApproved();
    }

    public function getKycStatusLabelAttribute(): string
    {
        return match($this->kyc_status) {
            'none' => 'ยังไม่ได้ยืนยัน',
            'pending' => 'รอตรวจสอบ',
            'approved' => 'ยืนยันแล้ว',
            'rejected' => 'ไม่ผ่านการยืนยัน',
            default => 'ไม่ทราบ',
        };
    }
}
