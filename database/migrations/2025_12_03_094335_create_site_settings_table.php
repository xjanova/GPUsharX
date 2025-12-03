<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, text, boolean, integer, json, image
            $table->string('group')->default('general'); // general, appearance, seo, social, etc.
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }

    /**
     * Seed default settings
     */
    protected function seedDefaultSettings(): void
    {
        $settings = [
            // General
            ['key' => 'site_name', 'value' => 'GPU Share X', 'type' => 'string', 'group' => 'general', 'label' => 'ชื่อเว็บไซต์'],
            ['key' => 'site_tagline', 'value' => 'แพลตฟอร์ม AI Generation แบบกระจาย', 'type' => 'string', 'group' => 'general', 'label' => 'คำโปรย'],
            ['key' => 'site_description', 'value' => 'สร้างภาพและวีดีโอด้วย AI บนเครือข่าย GPU แบบกระจาย', 'type' => 'text', 'group' => 'general', 'label' => 'คำอธิบายเว็บไซต์'],
            ['key' => 'contact_email', 'value' => 'contact@gpusharex.com', 'type' => 'string', 'group' => 'general', 'label' => 'อีเมลติดต่อ'],

            // Appearance
            ['key' => 'site_logo', 'value' => null, 'type' => 'image', 'group' => 'appearance', 'label' => 'โลโก้หลัก'],
            ['key' => 'site_logo_light', 'value' => null, 'type' => 'image', 'group' => 'appearance', 'label' => 'โลโก้ (พื้นสว่าง)'],
            ['key' => 'site_favicon', 'value' => null, 'type' => 'image', 'group' => 'appearance', 'label' => 'Favicon'],
            ['key' => 'primary_color', 'value' => '#8B5CF6', 'type' => 'string', 'group' => 'appearance', 'label' => 'สีหลัก'],
            ['key' => 'secondary_color', 'value' => '#EC4899', 'type' => 'string', 'group' => 'appearance', 'label' => 'สีรอง'],

            // SEO
            ['key' => 'meta_keywords', 'value' => 'AI, GPU, Image Generation, Video Generation, Stable Diffusion', 'type' => 'text', 'group' => 'seo', 'label' => 'Meta Keywords'],
            ['key' => 'og_image', 'value' => null, 'type' => 'image', 'group' => 'seo', 'label' => 'OG Image'],

            // Social
            ['key' => 'facebook_url', 'value' => null, 'type' => 'string', 'group' => 'social', 'label' => 'Facebook URL'],
            ['key' => 'twitter_url', 'value' => null, 'type' => 'string', 'group' => 'social', 'label' => 'Twitter URL'],
            ['key' => 'discord_url', 'value' => null, 'type' => 'string', 'group' => 'social', 'label' => 'Discord URL'],
            ['key' => 'github_url', 'value' => null, 'type' => 'string', 'group' => 'social', 'label' => 'GitHub URL'],

            // Footer
            ['key' => 'footer_text', 'value' => '© 2024 GPU Share X. All rights reserved.', 'type' => 'text', 'group' => 'footer', 'label' => 'ข้อความ Footer'],
        ];

        foreach ($settings as $setting) {
            \DB::table('site_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
