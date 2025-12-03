<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'billing_period',
        'price',
        'original_price',
        'currency',
        'credits_amount',
        'bonus_credits',
        'monthly_credits',
        'priority_level',
        'unlimited_generations',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'unlimited_generations' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCreditsPackages($query)
    {
        return $query->where('type', 'credits');
    }

    public function scopeSubscriptions($query)
    {
        return $query->where('type', 'subscription');
    }

    public function getTotalCreditsAttribute(): int
    {
        return $this->credits_amount + $this->bonus_credits;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if ($this->original_price && $this->original_price > $this->price) {
            return (int) round((($this->original_price - $this->price) / $this->original_price) * 100);
        }
        return null;
    }

    /**
     * Get default packages for seeding
     */
    public static function getDefaultPackages(): array
    {
        return [
            // Credit packages
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'เริ่มต้นใช้งาน',
                'type' => 'credits',
                'billing_period' => 'one_time',
                'price' => 9.99,
                'credits_amount' => 100,
                'bonus_credits' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Popular',
                'slug' => 'popular',
                'description' => 'ยอดนิยม',
                'type' => 'credits',
                'billing_period' => 'one_time',
                'price' => 24.99,
                'original_price' => 29.99,
                'credits_amount' => 300,
                'bonus_credits' => 30,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'สำหรับมืออาชีพ',
                'type' => 'credits',
                'billing_period' => 'one_time',
                'price' => 49.99,
                'original_price' => 59.99,
                'credits_amount' => 700,
                'bonus_credits' => 100,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'สำหรับองค์กร',
                'type' => 'credits',
                'billing_period' => 'one_time',
                'price' => 199.99,
                'original_price' => 249.99,
                'credits_amount' => 3000,
                'bonus_credits' => 500,
                'is_active' => true,
                'sort_order' => 4,
            ],

            // Subscription packages (disabled by default)
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'description' => 'แพ็กเกจรายเดือนพื้นฐาน',
                'type' => 'subscription',
                'billing_period' => 'monthly',
                'price' => 19.99,
                'monthly_credits' => 200,
                'priority_level' => 10,
                'features' => ['200 credits/month', 'Standard queue', 'Email support'],
                'is_active' => false, // ยังไม่เปิดใช้
                'sort_order' => 10,
            ],
            [
                'name' => 'Pro Monthly',
                'slug' => 'pro-monthly',
                'description' => 'แพ็กเกจรายเดือน Pro',
                'type' => 'subscription',
                'billing_period' => 'monthly',
                'price' => 49.99,
                'monthly_credits' => 600,
                'priority_level' => 50,
                'features' => ['600 credits/month', 'Priority queue', 'Priority support', 'API access'],
                'is_active' => false,
                'is_featured' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'Enterprise Monthly',
                'slug' => 'enterprise-monthly',
                'description' => 'แพ็กเกจรายเดือนสำหรับองค์กร',
                'type' => 'subscription',
                'billing_period' => 'monthly',
                'price' => 199.99,
                'monthly_credits' => 3000,
                'priority_level' => 100,
                'unlimited_generations' => false,
                'features' => ['3000 credits/month', 'Highest priority', 'Dedicated support', 'API access', 'Custom models'],
                'is_active' => false,
                'sort_order' => 12,
            ],
            [
                'name' => 'Pro Yearly',
                'slug' => 'pro-yearly',
                'description' => 'แพ็กเกจรายปี Pro (ประหยัด 20%)',
                'type' => 'subscription',
                'billing_period' => 'yearly',
                'price' => 479.99,
                'original_price' => 599.88,
                'monthly_credits' => 600,
                'priority_level' => 50,
                'features' => ['600 credits/month', 'Priority queue', 'Priority support', 'API access', '20% discount'],
                'is_active' => false,
                'sort_order' => 20,
            ],
        ];
    }
}
