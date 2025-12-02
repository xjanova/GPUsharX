<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        // Seed AI Models
        $models = [
            [
                'model_id' => 'sd-xl-1.0',
                'name' => 'Stable Diffusion XL 1.0',
                'huggingface_id' => 'stabilityai/stable-diffusion-xl-base-1.0',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'รุ่นล่าสุดของ Stable Diffusion ที่สร้างภาพคุณภาพสูงความละเอียด 1024x1024 พร้อมความสามารถในการเข้าใจ prompt ที่ดีขึ้นมาก',
                'thumbnail' => '/images/models/sdxl.jpg',
                'vram_required_mb' => 8192,
                'size_mb' => 6940,
                'default_params' => json_encode([
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 30,
                    'cfg_scale' => 7.5,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'cfg_scale', 'seed', 'negative_prompt']),
                'status' => 'available',
                'popularity' => 1000,
                'is_featured' => true,
            ],
            [
                'model_id' => 'flux-1-dev',
                'name' => 'FLUX.1 [dev]',
                'huggingface_id' => 'black-forest-labs/FLUX.1-dev',
                'type' => 'image',
                'category' => 'flux',
                'description' => 'โมเดล Text-to-Image รุ่นใหม่จาก Black Forest Labs ที่สร้างภาพที่สวยงามและสมจริงมาก',
                'thumbnail' => '/images/models/flux.jpg',
                'vram_required_mb' => 12288,
                'size_mb' => 23800,
                'default_params' => json_encode([
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 20,
                    'guidance_scale' => 3.5,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'guidance_scale', 'seed']),
                'status' => 'available',
                'popularity' => 950,
                'is_featured' => true,
            ],
            [
                'model_id' => 'flux-1-schnell',
                'name' => 'FLUX.1 [schnell]',
                'huggingface_id' => 'black-forest-labs/FLUX.1-schnell',
                'type' => 'image',
                'category' => 'flux',
                'description' => 'เวอร์ชันเร็วของ FLUX.1 ใช้เวลาสร้างภาพเพียง 4 steps แต่ยังคงคุณภาพที่ดี',
                'thumbnail' => '/images/models/flux-schnell.jpg',
                'vram_required_mb' => 12288,
                'size_mb' => 23800,
                'default_params' => json_encode([
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 4,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'seed']),
                'status' => 'available',
                'popularity' => 900,
                'is_featured' => true,
            ],
            [
                'model_id' => 'animatediff-v3',
                'name' => 'AnimateDiff v3',
                'huggingface_id' => 'guoyww/animatediff-motion-adapter-v1-5-3',
                'type' => 'video',
                'category' => 'animatediff',
                'description' => 'สร้างวิดีโอแอนิเมชันจากภาพนิ่งหรือ prompt โดยใช้ Motion Module ร่วมกับ Stable Diffusion',
                'thumbnail' => '/images/models/animatediff.jpg',
                'vram_required_mb' => 10240,
                'size_mb' => 1800,
                'default_params' => json_encode([
                    'width' => 512,
                    'height' => 512,
                    'steps' => 25,
                    'frames' => 16,
                    'fps' => 8,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'frames', 'fps', 'seed', 'negative_prompt']),
                'status' => 'available',
                'popularity' => 800,
                'is_featured' => true,
            ],
            [
                'model_id' => 'cogvideox-5b',
                'name' => 'CogVideoX-5B',
                'huggingface_id' => 'THUDM/CogVideoX-5b',
                'type' => 'video',
                'category' => 'cogvideo',
                'description' => 'โมเดลสร้างวิดีโอขนาดใหญ่ 5B parameters จาก Tsinghua University สร้างวิดีโอคุณภาพสูง',
                'thumbnail' => '/images/models/cogvideo.jpg',
                'vram_required_mb' => 24576,
                'size_mb' => 19000,
                'default_params' => json_encode([
                    'width' => 720,
                    'height' => 480,
                    'frames' => 49,
                    'fps' => 8,
                ]),
                'supported_params' => json_encode(['width', 'height', 'frames', 'fps', 'seed']),
                'status' => 'available',
                'popularity' => 700,
                'is_featured' => false,
            ],
            [
                'model_id' => 'sd-1.5',
                'name' => 'Stable Diffusion 1.5',
                'huggingface_id' => 'stable-diffusion-v1-5/stable-diffusion-v1-5',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'โมเดลคลาสสิคที่ยังคงเป็นที่นิยม เพราะใช้ VRAM น้อยและมี LoRA/ControlNet มากมาย',
                'thumbnail' => '/images/models/sd15.jpg',
                'vram_required_mb' => 4096,
                'size_mb' => 4270,
                'default_params' => json_encode([
                    'width' => 512,
                    'height' => 512,
                    'steps' => 30,
                    'cfg_scale' => 7.5,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'cfg_scale', 'seed', 'negative_prompt']),
                'status' => 'available',
                'popularity' => 850,
                'is_featured' => false,
            ],
            [
                'model_id' => 'sd-3-medium',
                'name' => 'Stable Diffusion 3 Medium',
                'huggingface_id' => 'stabilityai/stable-diffusion-3-medium',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'รุ่นใหม่ล่าสุดจาก Stability AI ใช้สถาปัตยกรรม MMDiT สร้างภาพคุณภาพสูงมาก',
                'thumbnail' => '/images/models/sd3.jpg',
                'vram_required_mb' => 10240,
                'size_mb' => 11400,
                'default_params' => json_encode([
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 28,
                    'cfg_scale' => 7.0,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'cfg_scale', 'seed', 'negative_prompt']),
                'status' => 'available',
                'popularity' => 880,
                'is_featured' => true,
            ],
            [
                'model_id' => 'realvisxl-v4',
                'name' => 'RealVisXL V4.0',
                'huggingface_id' => 'SG161222/RealVisXL_V4.0',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'โมเดล SDXL ที่ fine-tune มาเพื่อสร้างภาพถ่ายที่สมจริงมาก เหมาะกับภาพบุคคลและภาพธรรมชาติ',
                'thumbnail' => '/images/models/realvis.jpg',
                'vram_required_mb' => 8192,
                'size_mb' => 6940,
                'default_params' => json_encode([
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 25,
                    'cfg_scale' => 5.0,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'cfg_scale', 'seed', 'negative_prompt']),
                'status' => 'available',
                'popularity' => 820,
                'is_featured' => false,
            ],
            [
                'model_id' => 'dreamshaper-xl',
                'name' => 'DreamShaper XL',
                'huggingface_id' => 'Lykon/dreamshaper-xl-v2-turbo',
                'type' => 'image',
                'category' => 'stable_diffusion',
                'description' => 'โมเดลยอดนิยมที่สร้างภาพได้หลากหลายสไตล์ ทั้งภาพจริงและภาพวาด',
                'thumbnail' => '/images/models/dreamshaper.jpg',
                'vram_required_mb' => 8192,
                'size_mb' => 6940,
                'default_params' => json_encode([
                    'width' => 1024,
                    'height' => 1024,
                    'steps' => 8,
                    'cfg_scale' => 2.0,
                ]),
                'supported_params' => json_encode(['width', 'height', 'steps', 'cfg_scale', 'seed', 'negative_prompt']),
                'status' => 'available',
                'popularity' => 790,
                'is_featured' => false,
            ],
        ];

        foreach ($models as $model) {
            AiModel::updateOrCreate(
                ['model_id' => $model['model_id']],
                $model
            );
        }

        // Seed Platform Settings
        $settings = [
            // Referral settings
            ['key' => 'referral_level_1_rate', 'value' => '5', 'type' => 'number', 'group' => 'referral', 'description' => 'ค่าคอมมิชชันระดับ 1 (%)'],
            ['key' => 'referral_level_2_rate', 'value' => '2', 'type' => 'number', 'group' => 'referral', 'description' => 'ค่าคอมมิชชันระดับ 2 (%)'],
            ['key' => 'referral_level_3_rate', 'value' => '1', 'type' => 'number', 'group' => 'referral', 'description' => 'ค่าคอมมิชชันระดับ 3 (%)'],

            // Job distribution settings
            ['key' => 'fair_distribution', 'value' => 'true', 'type' => 'boolean', 'group' => 'jobs', 'description' => 'กระจายงานอย่างเป็นธรรม'],
            ['key' => 'priority_by_rank', 'value' => 'true', 'type' => 'boolean', 'group' => 'jobs', 'description' => 'ให้ความสำคัญตาม Rank'],
            ['key' => 'max_jobs_per_node', 'value' => '5', 'type' => 'number', 'group' => 'jobs', 'description' => 'จำนวนงานสูงสุดต่อ Node'],

            // Earnings settings
            ['key' => 'base_rate_per_step', 'value' => '0.001', 'type' => 'number', 'group' => 'earnings', 'description' => 'ค่าตอบแทนต่อ step (USD)'],
            ['key' => 'platform_fee_percent', 'value' => '10', 'type' => 'number', 'group' => 'earnings', 'description' => 'ค่าธรรมเนียมแพลตฟอร์ม (%)'],
            ['key' => 'min_payout', 'value' => '10', 'type' => 'number', 'group' => 'earnings', 'description' => 'ยอดขั้นต่ำในการถอน (USD)'],

            // Benchmark thresholds
            ['key' => 'rank_bronze_min', 'value' => '0', 'type' => 'number', 'group' => 'ranking', 'description' => 'คะแนนขั้นต่ำ Bronze'],
            ['key' => 'rank_silver_min', 'value' => '5000', 'type' => 'number', 'group' => 'ranking', 'description' => 'คะแนนขั้นต่ำ Silver'],
            ['key' => 'rank_gold_min', 'value' => '10000', 'type' => 'number', 'group' => 'ranking', 'description' => 'คะแนนขั้นต่ำ Gold'],
            ['key' => 'rank_platinum_min', 'value' => '20000', 'type' => 'number', 'group' => 'ranking', 'description' => 'คะแนนขั้นต่ำ Platinum'],
            ['key' => 'rank_diamond_min', 'value' => '35000', 'type' => 'number', 'group' => 'ranking', 'description' => 'คะแนนขั้นต่ำ Diamond'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
