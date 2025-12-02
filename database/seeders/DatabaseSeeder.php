<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RenderJob;
use App\Models\JobChunk;
use App\Models\PoolStatistic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@gpushare.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create Test User
        User::create([
            'name' => 'Test User',
            'email' => 'test@gpushare.com',
            'password' => bcrypt('test123'),
            'role' => 'user',
            'status' => 'active',
            'balance' => 150.50,
            'total_earned' => 250.75,
            'pending_earnings' => 25.00,
        ]);

        // Create Sample Render Jobs with Chunks
        $jobTypes = ['image', 'video', 'animation', '3d_render'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        for ($i = 1; $i <= 10; $i++) {
            $status = $i <= 3 ? 'queued' : ($i <= 6 ? 'processing' : 'completed');
            $totalChunks = rand(3, 10);
            $completedChunks = $status === 'queued' ? 0 : ($status === 'processing' ? rand(1, $totalChunks - 1) : $totalChunks);

            $job = RenderJob::create([
                'job_id' => 'JOB-' . strtoupper(Str::random(12)),
                'title' => "Sample Render Job #{$i}",
                'description' => "This is a sample render job for testing.",
                'type' => $jobTypes[array_rand($jobTypes)],
                'priority' => $priorities[array_rand($priorities)],
                'status' => $status,
                'estimated_credits' => rand(50, 500),
                'required_vram_mb' => [4096, 6144, 8192, 12288][array_rand([4096, 6144, 8192, 12288])],
                'total_chunks' => $totalChunks,
                'completed_chunks' => $completedChunks,
                'job_params' => ['prompt' => 'A beautiful landscape', 'resolution' => '1024x1024'],
                'created_by' => $admin->id,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            // Create chunks for each job
            $creditsPerChunk = round($job->estimated_credits / $totalChunks, 2);
            for ($c = 0; $c < $totalChunks; $c++) {
                $chunkStatus = 'pending';
                if ($status === 'completed' || $c < $completedChunks) {
                    $chunkStatus = 'completed';
                } elseif ($status === 'processing' && $c == $completedChunks) {
                    $chunkStatus = 'processing';
                }

                JobChunk::create([
                    'chunk_id' => 'CHUNK-' . strtoupper(Str::random(10)),
                    'render_job_id' => $job->id,
                    'chunk_index' => $c,
                    'status' => $chunkStatus,
                    'chunk_params' => ['frame_start' => $c * 10, 'frame_end' => ($c + 1) * 10 - 1],
                    'credits_earned' => $chunkStatus === 'completed' ? $creditsPerChunk : 0,
                    'completed_at' => $chunkStatus === 'completed' ? now() : null,
                ]);
            }
        }

        // Pool statistics for last 7 days
        for ($i = 6; $i >= 0; $i--) {
            PoolStatistic::create([
                'date' => now()->subDays($i)->toDateString(),
                'total_nodes' => rand(50, 150),
                'active_nodes' => rand(20, 80),
                'total_hashrate' => rand(100, 500),
                'jobs_completed' => rand(10, 50),
                'chunks_processed' => rand(50, 200),
                'credits_distributed' => rand(500, 2000),
                'platform_revenue' => rand(50, 200),
                'new_users' => rand(1, 20),
            ]);
        }

        // Seed AI Models and Platform Settings
        $this->call(AiModelSeeder::class);
    }
}
