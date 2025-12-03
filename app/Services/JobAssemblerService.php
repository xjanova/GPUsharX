<?php

namespace App\Services;

use App\Models\RenderJob;
use App\Models\JobChunk;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

/**
 * JobAssemblerService - รวมผลลัพธ์จากหลาย Nodes
 *
 * หน้าที่:
 * 1. ตรวจสอบว่า chunks ทั้งหมดเสร็จหรือยัง
 * 2. Download partial results จากทุก chunks
 * 3. ประกอบรวมกันเป็น Final result
 * 4. อัพโหลด Final result และแจ้ง Customer
 */
class JobAssemblerService
{
    /**
     * ตรวจสอบและประกอบ Job เมื่อ chunks ทั้งหมดเสร็จ
     */
    public function checkAndAssemble(RenderJob $job): bool
    {
        // ตรวจสอบว่า chunks ทั้งหมดเสร็จหรือยัง
        $totalChunks = $job->chunks()->count();
        $completedChunks = $job->chunks()->where('status', 'completed')->count();

        if ($completedChunks < $totalChunks) {
            // ยังไม่ครบ
            return false;
        }

        // อัพเดทสถานะ
        $job->update([
            'assembly_status' => 'ready',
        ]);

        // เริ่มประกอบ
        return $this->assemble($job);
    }

    /**
     * ประกอบผลลัพธ์
     */
    public function assemble(RenderJob $job): bool
    {
        $job->update([
            'assembly_status' => 'assembling',
            'assembly_started_at' => now(),
        ]);

        try {
            // Check if this is an admin test job
            $isAdminTest = $job->job_params['is_admin_test'] ?? false;

            // Check if we have real uploaded results from workers
            $hasRealResults = $job->chunks()->whereNotNull('partial_result_url')->exists();

            if ($hasRealResults) {
                // Use real uploaded results from worker
                $result = $this->assembleFromPartialResults($job);
            } elseif ($isAdminTest) {
                // Fallback to simulated result for admin tests without real uploads
                $result = $this->createSimulatedResult($job);
            } else {
                $result = match ($job->chunking_strategy) {
                    'tile_based' => $this->assembleTiles($job),
                    'step_based' => $this->assembleSteps($job),
                    'batch_based' => $this->assembleBatch($job),
                    'hybrid' => $this->assembleHybrid($job),
                    default => $this->assembleTiles($job),
                };
            }

            // บันทึกผลลัพธ์
            $job->update([
                'assembly_status' => 'completed',
                'final_result_url' => $result['url'],
                'status' => 'completed',
                'completed_at' => now(),
                'assembly_completed_at' => now(),
            ]);

            // แจ้ง Customer / อัพเดท GenerationJob
            $this->notifyJobCompleted($job, $result);

            Log::info("Job {$job->job_id} assembled successfully", [
                'total_chunks' => $job->chunks()->count(),
                'final_url' => $result['url'],
                'is_admin_test' => $isAdminTest,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Job assembly failed: {$job->job_id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $job->update([
                'assembly_status' => 'failed',
            ]);

            return false;
        }
    }

    /**
     * Assemble from real uploaded partial results
     */
    private function assembleFromPartialResults(RenderJob $job): array
    {
        $chunks = $job->chunks()->orderBy('chunk_index')->get();

        // For single chunk jobs, just copy the partial result
        if ($chunks->count() === 1) {
            $chunk = $chunks->first();
            $sourceUrl = $chunk->partial_result_url;

            // Parse the source path from URL
            $sourcePath = str_replace(asset('storage') . '/', '', $sourceUrl);

            // If it's already a full URL from storage, extract path
            if (str_starts_with($sourcePath, 'http')) {
                $sourcePath = parse_url($sourceUrl, PHP_URL_PATH);
                $sourcePath = str_replace('/storage/', '', $sourcePath);
            }

            // Copy to final result location
            $outputPath = "results/{$job->job_id}_final.png";

            // Check if source exists in storage
            if (Storage::disk('public')->exists($sourcePath)) {
                Storage::disk('public')->copy($sourcePath, $outputPath);
            } else {
                // Try to download from URL
                $imageData = @file_get_contents($sourceUrl);
                if ($imageData) {
                    Storage::disk('public')->put($outputPath, $imageData);
                } else {
                    throw new \Exception("Cannot access partial result: {$sourceUrl}");
                }
            }

            // Get image dimensions
            $fullPath = storage_path("app/public/{$outputPath}");
            $imageInfo = @getimagesize($fullPath);
            $width = $imageInfo[0] ?? 1024;
            $height = $imageInfo[1] ?? 1024;

            return [
                'url' => asset("storage/{$outputPath}"),
                'path' => $outputPath,
                'width' => $width,
                'height' => $height,
                'source' => 'worker_upload',
            ];
        }

        // For multiple chunks, use tile assembly
        return $this->assembleTiles($job);
    }

    /**
     * สร้าง Simulated Result สำหรับ Admin Test Jobs
     */
    private function createSimulatedResult(RenderJob $job): array
    {
        $params = $job->job_params ?? [];
        $width = $params['width'] ?? 1024;
        $height = $params['height'] ?? 1024;
        $prompt = $params['prompt'] ?? 'Test Image';

        // สร้างรูป placeholder
        $image = imagecreatetruecolor($width, $height);

        // Gradient background
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = (int)(100 + 80 * $ratio);
            $g = (int)(50 + 100 * $ratio);
            $b = (int)(150 + 50 * (1 - $ratio));
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Add text overlay
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $shadowColor = imagecolorallocate($image, 0, 0, 0);

        // Title
        $title = "Admin Test Result";
        imagestring($image, 5, $width/2 - 70 + 1, 20 + 1, $title, $shadowColor);
        imagestring($image, 5, $width/2 - 70, 20, $title, $textColor);

        // Job ID
        $jobText = "Job: " . $job->job_id;
        imagestring($image, 3, 20 + 1, $height - 60 + 1, $jobText, $shadowColor);
        imagestring($image, 3, 20, $height - 60, $jobText, $textColor);

        // Prompt (truncated)
        $promptText = "Prompt: " . substr($prompt, 0, 50) . (strlen($prompt) > 50 ? '...' : '');
        imagestring($image, 2, 20 + 1, $height - 40 + 1, $promptText, $shadowColor);
        imagestring($image, 2, 20, $height - 40, $promptText, $textColor);

        // Timestamp
        $timeText = "Generated: " . now()->format('Y-m-d H:i:s');
        imagestring($image, 2, 20 + 1, $height - 20 + 1, $timeText, $shadowColor);
        imagestring($image, 2, 20, $height - 20, $timeText, $textColor);

        // Center decoration
        $centerX = $width / 2;
        $centerY = $height / 2;
        for ($i = 0; $i < 5; $i++) {
            $radius = 50 + $i * 30;
            $alpha = 100 - $i * 15;
            $circleColor = imagecolorallocatealpha($image, 255, 255, 255, $alpha);
            imageellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $circleColor);
        }

        // บันทึกไฟล์
        $outputPath = "results/{$job->job_id}_final.png";
        $fullPath = storage_path("app/public/{$outputPath}");

        // สร้าง directory ถ้ายังไม่มี
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($image, $fullPath, 9);
        imagedestroy($image);

        return [
            'url' => asset("storage/{$outputPath}"),
            'path' => $outputPath,
            'width' => $width,
            'height' => $height,
            'simulated' => true,
        ];
    }

    /**
     * ประกอบ Tiles เข้าด้วยกัน
     */
    private function assembleTiles(RenderJob $job): array
    {
        $config = $job->parallel_config ?? [];
        $width = $config['width'] ?? 1024;
        $height = $config['height'] ?? 1024;
        $overlap = $config['overlap'] ?? 64;
        $blendMode = $config['blend_mode'] ?? 'linear';

        // สร้าง canvas
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // Fill with transparent/white
        $bgColor = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $bgColor);

        // ดึง chunks เรียงตาม index
        $chunks = $job->chunks()->orderBy('chunk_index')->get();

        foreach ($chunks as $chunk) {
            $tileConfig = $chunk->chunk_config;

            // Load tile image
            $tileImage = $this->loadImageFromUrl($chunk->partial_result_url);
            if (!$tileImage) {
                throw new \Exception("Failed to load tile: {$chunk->chunk_id}");
            }

            // วาง tile ลงบน canvas พร้อม blend
            $this->placeTileWithBlend(
                $canvas,
                $tileImage,
                $tileConfig['x'],
                $tileConfig['y'],
                $overlap,
                $blendMode
            );

            imagedestroy($tileImage);
        }

        // บันทึก final image
        $outputPath = "results/{$job->job_id}_final.png";
        $fullPath = storage_path("app/public/{$outputPath}");

        // สร้าง directory ถ้ายังไม่มี
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($canvas, $fullPath, 9);
        imagedestroy($canvas);

        return [
            'url' => asset("storage/{$outputPath}"),
            'path' => $outputPath,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * ประกอบ Steps (Step-based ไม่ต้อง assemble จริงๆ เพราะ chunk สุดท้ายคือ final)
     */
    private function assembleSteps(RenderJob $job): array
    {
        // สำหรับ step-based, chunk สุดท้ายคือผลลัพธ์สุดท้าย
        $lastChunk = $job->chunks()
            ->orderBy('chunk_index', 'desc')
            ->first();

        if (!$lastChunk || !$lastChunk->partial_result_url) {
            throw new \Exception("Final step chunk not found or has no result");
        }

        // Copy final result
        $outputPath = "results/{$job->job_id}_final.png";
        Storage::disk('public')->copy(
            str_replace(asset('storage/'), '', $lastChunk->partial_result_url),
            $outputPath
        );

        return [
            'url' => asset("storage/{$outputPath}"),
            'path' => $outputPath,
        ];
    }

    /**
     * ประกอบ Batch (รวมรูปทั้งหมดเป็น gallery)
     */
    private function assembleBatch(RenderJob $job): array
    {
        $chunks = $job->chunks()->orderBy('chunk_index')->get();
        $results = [];

        foreach ($chunks as $chunk) {
            $results[] = [
                'index' => $chunk->chunk_index,
                'url' => $chunk->partial_result_url,
                'seed' => $chunk->chunk_config['seed'] ?? null,
            ];
        }

        // สร้าง gallery metadata
        $outputPath = "results/{$job->job_id}_gallery.json";
        Storage::disk('public')->put($outputPath, json_encode([
            'job_id' => $job->job_id,
            'total_images' => count($results),
            'images' => $results,
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        // สร้าง composite image (grid)
        $gridImage = $this->createImageGrid($chunks);
        $gridPath = "results/{$job->job_id}_grid.png";
        imagepng($gridImage, storage_path("app/public/{$gridPath}"), 9);
        imagedestroy($gridImage);

        return [
            'url' => asset("storage/{$gridPath}"),
            'gallery_url' => asset("storage/{$outputPath}"),
            'images' => $results,
        ];
    }

    /**
     * ประกอบ Hybrid
     */
    private function assembleHybrid(RenderJob $job): array
    {
        // Hybrid ใช้ tile-based assembly
        return $this->assembleTiles($job);
    }

    /**
     * โหลดรูปจาก URL
     */
    private function loadImageFromUrl(string $url): ?\GdImage
    {
        // ถ้าเป็น local storage
        if (str_starts_with($url, asset('storage/'))) {
            $path = str_replace(asset('storage/'), '', $url);
            $fullPath = storage_path("app/public/{$path}");

            if (file_exists($fullPath)) {
                return imagecreatefrompng($fullPath);
            }
        }

        // ถ้าเป็น external URL
        $imageData = @file_get_contents($url);
        if ($imageData) {
            return imagecreatefromstring($imageData);
        }

        return null;
    }

    /**
     * วาง Tile ลงบน Canvas พร้อม Blend ขอบ
     */
    private function placeTileWithBlend(
        \GdImage $canvas,
        \GdImage $tile,
        int $x,
        int $y,
        int $overlap,
        string $blendMode
    ): void {
        $tileWidth = imagesx($tile);
        $tileHeight = imagesy($tile);

        for ($ty = 0; $ty < $tileHeight; $ty++) {
            for ($tx = 0; $tx < $tileWidth; $tx++) {
                $canvasX = $x + $tx;
                $canvasY = $y + $ty;

                // ตรวจสอบขอบเขต
                if ($canvasX >= imagesx($canvas) || $canvasY >= imagesy($canvas)) {
                    continue;
                }

                $tileColor = imagecolorat($tile, $tx, $ty);

                // คำนวณ blend weight สำหรับ overlap zone
                $weight = $this->calculateBlendWeight(
                    $tx, $ty, $tileWidth, $tileHeight, $overlap, $blendMode
                );

                if ($weight >= 0.99) {
                    // ไม่ต้อง blend
                    imagesetpixel($canvas, $canvasX, $canvasY, $tileColor);
                } else if ($weight > 0.01) {
                    // Blend กับ pixel เดิม
                    $canvasColor = imagecolorat($canvas, $canvasX, $canvasY);
                    $blendedColor = $this->blendColors($canvas, $canvasColor, $tileColor, $weight);
                    imagesetpixel($canvas, $canvasX, $canvasY, $blendedColor);
                }
                // weight <= 0.01 → ใช้ pixel เดิม (ไม่วาง)
            }
        }
    }

    /**
     * คำนวณ Blend Weight ตาม position ใน overlap zone
     */
    private function calculateBlendWeight(
        int $x,
        int $y,
        int $width,
        int $height,
        int $overlap,
        string $mode
    ): float {
        // ถ้าไม่มี overlap
        if ($overlap <= 0) {
            return 1.0;
        }

        // คำนวณระยะจากขอบ
        $distLeft = $x;
        $distRight = $width - 1 - $x;
        $distTop = $y;
        $distBottom = $height - 1 - $y;

        $minDist = min($distLeft, $distRight, $distTop, $distBottom);

        // ถ้าอยู่ในส่วน overlap
        if ($minDist < $overlap) {
            $t = $minDist / $overlap;

            return match ($mode) {
                'linear' => $t,
                'gaussian' => $this->gaussianBlend($t),
                'smoothstep' => $this->smoothstep($t),
                default => $t,
            };
        }

        return 1.0;
    }

    /**
     * Gaussian blend curve
     */
    private function gaussianBlend(float $t): float
    {
        return 1.0 - exp(-3.0 * $t * $t);
    }

    /**
     * Smoothstep blend curve
     */
    private function smoothstep(float $t): float
    {
        return $t * $t * (3.0 - 2.0 * $t);
    }

    /**
     * Blend two colors
     */
    private function blendColors(\GdImage $img, int $color1, int $color2, float $weight): int
    {
        $r1 = ($color1 >> 16) & 0xFF;
        $g1 = ($color1 >> 8) & 0xFF;
        $b1 = $color1 & 0xFF;

        $r2 = ($color2 >> 16) & 0xFF;
        $g2 = ($color2 >> 8) & 0xFF;
        $b2 = $color2 & 0xFF;

        $r = (int) ($r1 * (1 - $weight) + $r2 * $weight);
        $g = (int) ($g1 * (1 - $weight) + $g2 * $weight);
        $b = (int) ($b1 * (1 - $weight) + $b2 * $weight);

        return imagecolorallocate($img, $r, $g, $b);
    }

    /**
     * สร้าง Image Grid จาก batch items
     */
    private function createImageGrid($chunks): \GdImage
    {
        $count = $chunks->count();
        $cols = ceil(sqrt($count));
        $rows = ceil($count / $cols);

        // ขนาด thumbnail
        $thumbSize = 256;
        $padding = 4;

        $gridWidth = $cols * ($thumbSize + $padding) + $padding;
        $gridHeight = $rows * ($thumbSize + $padding) + $padding;

        $grid = imagecreatetruecolor($gridWidth, $gridHeight);
        $bgColor = imagecolorallocate($grid, 32, 32, 32);
        imagefill($grid, 0, 0, $bgColor);

        $index = 0;
        foreach ($chunks as $chunk) {
            $col = $index % $cols;
            $row = (int) ($index / $cols);

            $x = $padding + $col * ($thumbSize + $padding);
            $y = $padding + $row * ($thumbSize + $padding);

            $image = $this->loadImageFromUrl($chunk->partial_result_url);
            if ($image) {
                // Resize to thumbnail
                $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
                imagecopyresampled(
                    $thumb, $image,
                    0, 0, 0, 0,
                    $thumbSize, $thumbSize,
                    imagesx($image), imagesy($image)
                );

                imagecopy($grid, $thumb, $x, $y, 0, 0, $thumbSize, $thumbSize);
                imagedestroy($thumb);
                imagedestroy($image);
            }

            $index++;
        }

        return $grid;
    }

    /**
     * แจ้ง Customer ว่างานเสร็จแล้ว
     */
    private function notifyJobCompleted(RenderJob $job, array $result): void
    {
        Log::info("Job completed notification", [
            'job_id' => $job->job_id,
            'user_id' => $job->created_by,
            'result_url' => $result['url'],
        ]);

        // อัพเดท GenerationJob ถ้ามี
        $generationJobId = $job->job_params['generation_job_id'] ?? null;
        if ($generationJobId) {
            $generationJob = \App\Models\GenerationJob::find($generationJobId);
            if ($generationJob) {
                $processingTime = $generationJob->created_at
                    ? $generationJob->created_at->diffInMilliseconds(now())
                    : null;

                $generationJob->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'result_url' => $result['url'],
                    'result_thumbnail' => $result['url'],
                    'processing_time_ms' => $processingTime,
                    'result_metadata' => [
                        'width' => $result['width'] ?? null,
                        'height' => $result['height'] ?? null,
                        'simulated' => $result['simulated'] ?? false,
                        'completed_at' => now()->toIso8601String(),
                    ],
                ]);

                Log::info("GenerationJob updated", [
                    'generation_job_id' => $generationJobId,
                    'status' => 'completed',
                    'result_url' => $result['url'],
                ]);
            }
        }
    }

    /**
     * Retry assembly สำหรับ jobs ที่ failed
     */
    public function retryFailedAssemblies(): int
    {
        $failedJobs = RenderJob::where('assembly_status', 'failed')
            ->where('status', '!=', 'cancelled')
            ->get();

        $retried = 0;
        foreach ($failedJobs as $job) {
            if ($this->assemble($job)) {
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * ลบ partial results หลังจาก assembly เสร็จ
     */
    public function cleanupPartialResults(RenderJob $job): void
    {
        if ($job->assembly_status !== 'completed') {
            return;
        }

        foreach ($job->chunks as $chunk) {
            if ($chunk->partial_result_url) {
                $path = str_replace(asset('storage/'), '', $chunk->partial_result_url);
                Storage::disk('public')->delete($path);

                $chunk->update(['partial_result_url' => null]);
            }
        }
    }
}
