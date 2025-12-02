<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            return match($setting->type) {
                'integer' => (int) $setting->value,
                'float' => (float) $setting->value,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): void
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storeValue, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting.{$key}");
    }

    public static function getDefaultSettings(): array
    {
        return [
            // Platform fees
            ['key' => 'platform_fee_percentage', 'value' => '10', 'type' => 'float', 'group' => 'fees', 'description' => 'Platform fee percentage on earnings'],
            ['key' => 'minimum_payout', 'value' => '10', 'type' => 'float', 'group' => 'fees', 'description' => 'Minimum payout amount in USD'],

            // Referral settings
            ['key' => 'referral_level_1_rate', 'value' => '5', 'type' => 'float', 'group' => 'referral', 'description' => 'Level 1 referral commission %'],
            ['key' => 'referral_level_2_rate', 'value' => '2', 'type' => 'float', 'group' => 'referral', 'description' => 'Level 2 referral commission %'],
            ['key' => 'referral_level_3_rate', 'value' => '1', 'type' => 'float', 'group' => 'referral', 'description' => 'Level 3 referral commission %'],
            ['key' => 'max_referral_levels', 'value' => '3', 'type' => 'integer', 'group' => 'referral', 'description' => 'Maximum referral tree depth'],

            // Job distribution
            ['key' => 'job_distribution_mode', 'value' => 'fair', 'type' => 'string', 'group' => 'jobs', 'description' => 'Job distribution mode: fair, performance, random'],
            ['key' => 'max_jobs_per_node', 'value' => '5', 'type' => 'integer', 'group' => 'jobs', 'description' => 'Max concurrent jobs per node'],
            ['key' => 'job_timeout_minutes', 'value' => '30', 'type' => 'integer', 'group' => 'jobs', 'description' => 'Job timeout in minutes'],

            // Credits
            ['key' => 'credits_per_image', 'value' => '1', 'type' => 'float', 'group' => 'credits', 'description' => 'Base credits for image generation'],
            ['key' => 'credits_per_video_second', 'value' => '5', 'type' => 'float', 'group' => 'credits', 'description' => 'Credits per second of video'],
            ['key' => 'credit_to_usd_rate', 'value' => '0.001', 'type' => 'float', 'group' => 'credits', 'description' => 'Credits to USD conversion rate'],

            // Branding
            ['key' => 'site_name', 'value' => 'GPU Share', 'type' => 'string', 'group' => 'branding', 'description' => 'Site name'],
            ['key' => 'company_name', 'value' => 'Xman Studio Thailand', 'type' => 'string', 'group' => 'branding', 'description' => 'Company name'],
            ['key' => 'copyright_year', 'value' => '2025', 'type' => 'string', 'group' => 'branding', 'description' => 'Copyright year'],
        ];
    }
}
