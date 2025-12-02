<?php

namespace Database\Seeders;

use App\Models\ClientVersion;
use Illuminate\Database\Seeder;

class ClientVersionSeeder extends Seeder
{
    public function run(): void
    {
        // Source version (always available)
        $sourceFile = storage_path('app/public/client-releases/GPUShareClient-1.0.0-source.zip');
        $checksum = file_exists($sourceFile) ? hash_file('sha256', $sourceFile) : null;
        $fileSize = file_exists($sourceFile) ? filesize($sourceFile) : 0;

        ClientVersion::updateOrCreate(
            ['version' => '1.0.0', 'platform' => 'source'],
            [
                'filename' => 'GPUShareClient-1.0.0-source.zip',
                'file_path' => 'client-releases/GPUShareClient-1.0.0-source.zip',
                'file_size' => $fileSize,
                'checksum_sha256' => $checksum,
                'release_notes' => "GPU Share Client v1.0.0 - Initial Release\n\n" .
                    "Features:\n" .
                    "- GPU Monitoring (temperature, power, memory, utilization)\n" .
                    "- Fan Control for GPU cooling\n" .
                    "- System Monitoring (CPU, RAM, Network)\n" .
                    "- Power Consumption Tracking\n" .
                    "- Job Processing with Status Indicators\n" .
                    "- Real-time Earnings Display\n\n" .
                    "Requirements:\n" .
                    "- Python 3.10+\n" .
                    "- NVIDIA GPU with CUDA support",
                'min_gpu_memory' => '4GB',
                'min_ram' => '8GB',
                'is_latest' => true,
                'is_active' => $fileSize > 0,
            ]
        );

        // Windows C Source Code
        $winSourceFile = storage_path('app/public/client-releases/GPUShareClient-1.0.0-windows-source.zip');
        $winChecksum = file_exists($winSourceFile) ? hash_file('sha256', $winSourceFile) : null;
        $winFileSize = file_exists($winSourceFile) ? filesize($winSourceFile) : 0;

        ClientVersion::updateOrCreate(
            ['version' => '1.0.0', 'platform' => 'windows'],
            [
                'filename' => 'GPUShareClient-1.0.0-windows-source.zip',
                'file_path' => 'client-releases/GPUShareClient-1.0.0-windows-source.zip',
                'file_size' => $winFileSize,
                'checksum_sha256' => $winChecksum,
                'release_notes' => "GPU Share Client v1.0.0 for Windows (C Source)\n\n" .
                    "Native Windows application written in C with professional dark theme dashboard.\n\n" .
                    "Features:\n" .
                    "- GPU Monitoring (temperature, power, memory, utilization)\n" .
                    "- System Monitoring (CPU, RAM, Network)\n" .
                    "- Job Processing with Status Indicators\n" .
                    "- Real-time Earnings Display\n" .
                    "- Bilingual UI (English/Thai)\n\n" .
                    "Build Instructions:\n" .
                    "1. Extract zip file\n" .
                    "2. Open Developer Command Prompt for VS\n" .
                    "3. Run: build.bat\n\n" .
                    "Or use MinGW: build-mingw.bat",
                'min_gpu_memory' => '4GB',
                'min_ram' => '8GB',
                'is_latest' => true,
                'is_active' => $winFileSize > 0,
            ]
        );

        // Linux version placeholder
        ClientVersion::updateOrCreate(
            ['version' => '1.0.0', 'platform' => 'linux'],
            [
                'filename' => 'GPUShareClient-1.0.0-linux.tar.gz',
                'file_path' => 'client-releases/GPUShareClient-1.0.0-linux.tar.gz',
                'file_size' => 0,
                'release_notes' => "GPU Share Client v1.0.0 for Linux\n\n" .
                    "Supports Ubuntu 20.04+ and Debian 11+\n\n" .
                    "Features:\n" .
                    "- GPU Monitoring (temperature, power, memory, utilization)\n" .
                    "- Fan Control for GPU cooling\n" .
                    "- System Monitoring (CPU, RAM, Network)\n" .
                    "- Power Consumption Tracking\n" .
                    "- Job Processing with Status Indicators\n" .
                    "- Real-time Earnings Display",
                'min_gpu_memory' => '4GB',
                'min_ram' => '8GB',
                'is_latest' => true,
                'is_active' => false, // Not available yet
            ]
        );
    }
}
