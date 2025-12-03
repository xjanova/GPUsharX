<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Referral Commission Rates
            [
                'key' => 'referral_level_1_rate',
                'value' => '5',
                'type' => 'float',
                'group' => 'referral',
                'description' => 'Commission rate for Level 1 referrals (direct) in percentage',
            ],
            [
                'key' => 'referral_level_2_rate',
                'value' => '2',
                'type' => 'float',
                'group' => 'referral',
                'description' => 'Commission rate for Level 2 referrals in percentage',
            ],
            [
                'key' => 'referral_level_3_rate',
                'value' => '1',
                'type' => 'float',
                'group' => 'referral',
                'description' => 'Commission rate for Level 3 referrals in percentage',
            ],
            [
                'key' => 'referral_max_levels',
                'value' => '3',
                'type' => 'integer',
                'group' => 'referral',
                'description' => 'Maximum referral tree depth',
            ],
            [
                'key' => 'referral_auto_pay_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'referral',
                'description' => 'Days before pending referral commissions are auto-paid',
            ],

            // Platform Fees
            [
                'key' => 'platform_fee_percent',
                'value' => '10',
                'type' => 'float',
                'group' => 'fees',
                'description' => 'Platform fee percentage on worker earnings',
            ],
            [
                'key' => 'withdrawal_fee_percent',
                'value' => '2',
                'type' => 'float',
                'group' => 'fees',
                'description' => 'Withdrawal fee percentage',
            ],
            [
                'key' => 'min_withdrawal_amount',
                'value' => '50',
                'type' => 'float',
                'group' => 'fees',
                'description' => 'Minimum withdrawal amount in USD',
            ],

            // Generation Credits
            [
                'key' => 'new_user_credits',
                'value' => '100',
                'type' => 'integer',
                'group' => 'credits',
                'description' => 'Free credits given to new users',
            ],
            [
                'key' => 'credits_per_usd',
                'value' => '100',
                'type' => 'integer',
                'group' => 'credits',
                'description' => 'How many credits per 1 USD',
            ],

            // File Retention
            [
                'key' => 'file_retention_hours',
                'value' => '48',
                'type' => 'integer',
                'group' => 'storage',
                'description' => 'Hours to keep generated files before deletion',
            ],

            // Worker Settings
            [
                'key' => 'max_chunk_retries',
                'value' => '3',
                'type' => 'integer',
                'group' => 'worker',
                'description' => 'Maximum retry attempts for failed job chunks',
            ],
            [
                'key' => 'worker_heartbeat_timeout',
                'value' => '300',
                'type' => 'integer',
                'group' => 'worker',
                'description' => 'Seconds before a worker is considered offline',
            ],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Platform settings seeded successfully!');
    }
}
