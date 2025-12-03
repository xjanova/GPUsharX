<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable key and secret key give you access to Stripe's
    | API. The "publishable" key is typically used when interacting with
    | Stripe.js while the "secret" key accesses private API endpoints.
    |
    */

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency used when charging credit cards. You're
    | free to update this to any currency supported by Stripe.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'thb'),

    /*
    |--------------------------------------------------------------------------
    | Credit Packages
    |--------------------------------------------------------------------------
    |
    | Define the credit packages available for purchase.
    | Price is in satangs (THB smallest unit, 1 THB = 100 satangs)
    |
    */

    'packages' => [
        'starter' => [
            'name' => 'Starter',
            'credits' => 50,
            'price' => 2900, // 29 THB in satangs
            'price_display' => 29,
            'description' => 'สำหรับทดลองใช้',
            'features' => [
                '~50 ภาพ SD',
                '~10 ภาพ HD',
                'ไม่มีวันหมดอายุ',
            ],
            'popular' => false,
            'discount' => null,
        ],
        'basic' => [
            'name' => 'Basic',
            'credits' => 200,
            'price' => 9900, // 99 THB
            'price_display' => 99,
            'description' => 'สำหรับใช้งานทั่วไป',
            'features' => [
                '~200 ภาพ SD',
                '~40 ภาพ HD',
                '~10 วีดีโอสั้น',
            ],
            'popular' => false,
            'discount' => null,
        ],
        'pro' => [
            'name' => 'Pro',
            'credits' => 500,
            'price' => 19900, // 199 THB
            'price_display' => 199,
            'original_price' => 300,
            'description' => 'คุ้มค่าที่สุด',
            'features' => [
                '~500 ภาพ SD',
                '~100 ภาพ HD',
                '~25 วีดีโอสั้น',
                'Priority Queue',
            ],
            'popular' => true,
            'discount' => 34,
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'credits' => 2000,
            'price' => 69900, // 699 THB
            'price_display' => 699,
            'original_price' => 1200,
            'description' => 'สำหรับมืออาชีพ',
            'features' => [
                '~2000 ภาพ SD',
                '~400 ภาพ HD',
                '~100 วีดีโอสั้น',
                'VIP Priority',
            ],
            'popular' => false,
            'discount' => 42,
        ],
    ],
];
