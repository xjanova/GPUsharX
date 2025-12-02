# GPU Sharing Platform - Parallel Processing Architecture

## สถาปัตยกรรมใหม่: Distributed Parallel Processing

### ปัญหาของระบบเดิม (Sequential)

```
┌─────────────────────────────────────────────────────────────────┐
│  ระบบเดิม: 1 Job → 1 Node (ช้ามากถ้า Node กำลังต่ำ)            │
└─────────────────────────────────────────────────────────────────┘

  Job: Generate 1024x1024 Image (100 steps)
       │
       ▼
  ┌─────────┐
  │ Node A  │ ──────────────────────────────────────▶ Result
  │ (Slow)  │        ⏱️ 60 seconds
  └─────────┘

  ❌ ปัญหา: Node กำลังต่ำทำให้งานช้ามาก
```

### สถาปัตยกรรมใหม่ (Parallel Processing)

```
┌─────────────────────────────────────────────────────────────────┐
│  ระบบใหม่: 1 Job → หลาย Nodes พร้อมกัน (เร็วขึ้นหลายเท่า)       │
└─────────────────────────────────────────────────────────────────┘

  Job: Generate 1024x1024 Image (100 steps)
       │
       │  Split by Steps (Denoising Steps)
       │  or Split by Tiles (Image Regions)
       │
       ├──────────────────┬──────────────────┬──────────────────┐
       ▼                  ▼                  ▼                  ▼
  ┌─────────┐        ┌─────────┐        ┌─────────┐        ┌─────────┐
  │ Node A  │        │ Node B  │        │ Node C  │        │ Node D  │
  │Steps 1-25│       │Steps 26-50│      │Steps 51-75│      │Steps 76-100│
  └────┬────┘        └────┬────┘        └────┬────┘        └────┬────┘
       │ 15s              │ 15s              │ 15s              │ 15s
       ▼                  ▼                  ▼                  ▼
  ┌─────────┐        ┌─────────┐        ┌─────────┐        ┌─────────┐
  │Partial 1│        │Partial 2│        │Partial 3│        │Partial 4│
  └────┬────┘        └────┬────┘        └────┬────┘        └────┬────┘
       │                  │                  │                  │
       └──────────────────┴──────────────────┴──────────────────┘
                                    │
                                    ▼
                          ┌─────────────────┐
                          │   ASSEMBLER     │
                          │ Combine Results │
                          └────────┬────────┘
                                   │
                                   ▼
                          ┌─────────────────┐
                          │  Final Image    │
                          │   1024x1024     │
                          └─────────────────┘

  ✅ ผลลัพธ์: 15 วินาที แทนที่จะเป็น 60 วินาที (เร็วขึ้น 4 เท่า!)
```

---

## วิธีการแบ่งงาน (Chunking Strategies)

### 1. Step-based Chunking (สำหรับ Diffusion Models)

เหมาะสำหรับ: Stable Diffusion, DALL-E style generation

```
┌─────────────────────────────────────────────────────────────────┐
│                    STEP-BASED CHUNKING                          │
└─────────────────────────────────────────────────────────────────┘

  Total: 100 Denoising Steps
  Nodes Available: 4

  ┌──────────────────────────────────────────────────────────────┐
  │ Node A: Steps 1-25   (Initial noise → Partial denoise)       │
  │ Node B: Steps 26-50  (Continue from Node A's latent)         │
  │ Node C: Steps 51-75  (Continue from Node B's latent)         │
  │ Node D: Steps 76-100 (Final refinement → Output image)       │
  └──────────────────────────────────────────────────────────────┘

  Data Flow:
  ┌────────┐    latent    ┌────────┐    latent    ┌────────┐
  │ Node A │ ───────────▶ │ Node B │ ───────────▶ │ Node C │ ...
  └────────┘              └────────┘              └────────┘

  ⚠️ ข้อจำกัด: ต้องรอ Node ก่อนหน้าเสร็จ (Sequential dependency)
```

### 2. Tile-based Chunking (สำหรับ High-res Images)

เหมาะสำหรับ: Upscaling, High-resolution generation

```
┌─────────────────────────────────────────────────────────────────┐
│                    TILE-BASED CHUNKING                          │
└─────────────────────────────────────────────────────────────────┘

  Original: 4096x4096 Image
  Split into: 4x4 = 16 Tiles (1024x1024 each)

  ┌────────┬────────┬────────┬────────┐
  │ Tile 0 │ Tile 1 │ Tile 2 │ Tile 3 │  → Node A, B, C, D
  ├────────┼────────┼────────┼────────┤
  │ Tile 4 │ Tile 5 │ Tile 6 │ Tile 7 │  → Node E, F, G, H
  ├────────┼────────┼────────┼────────┤
  │ Tile 8 │ Tile 9 │Tile 10 │Tile 11 │  → Node I, J, K, L
  ├────────┼────────┼────────┼────────┤
  │Tile 12 │Tile 13 │Tile 14 │Tile 15 │  → Node M, N, O, P
  └────────┴────────┴────────┴────────┘

  ✅ ข้อดี: ทุก Tile ทำพร้อมกันได้ (True parallel)
  ⚠️ ต้อง Blend ขอบ Tiles ให้เนียน
```

### 3. Batch-based Chunking (สำหรับ Multiple Outputs)

เหมาะสำหรับ: Generate หลายรูปจาก prompt เดียว

```
┌─────────────────────────────────────────────────────────────────┐
│                    BATCH-BASED CHUNKING                         │
└─────────────────────────────────────────────────────────────────┘

  Request: Generate 8 variations of "sunset over mountains"

  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐
  │ Node A │  │ Node B │  │ Node C │  │ Node D │
  │ Img 1-2│  │ Img 3-4│  │ Img 5-6│  │ Img 7-8│
  └────────┘  └────────┘  └────────┘  └────────┘
       │           │           │           │
       ▼           ▼           ▼           ▼
  [Img1,Img2] [Img3,Img4] [Img5,Img6] [Img7,Img8]
       │           │           │           │
       └───────────┴───────────┴───────────┘
                        │
                        ▼
                 [Final Gallery]
                 8 Images ready!

  ✅ ข้อดี: True parallel, ไม่ต้อง blend
```

### 4. Hybrid Chunking (ผสมผสาน)

```
┌─────────────────────────────────────────────────────────────────┐
│                     HYBRID CHUNKING                             │
└─────────────────────────────────────────────────────────────────┘

  Job: Generate 4K image with 100 steps

  Phase 1: Step-based (Lower resolution)
  ┌────────────────────────────────────────────┐
  │ Generate 512x512 base with distributed steps │
  └────────────────────────────────────────────┘
                      │
                      ▼
  Phase 2: Tile-based (Upscale to 4K)
  ┌────────────────────────────────────────────┐
  │ Upscale each 512x512 tile to 1024x1024     │
  │ 16 tiles processed in parallel              │
  └────────────────────────────────────────────┘
                      │
                      ▼
  Phase 3: Assemble
  ┌────────────────────────────────────────────┐
  │ Combine 16 tiles → Final 4096x4096 image   │
  └────────────────────────────────────────────┘
```

---

## Database Schema Updates

### jobs table (เพิ่ม fields)

```sql
ALTER TABLE render_jobs ADD COLUMN chunking_strategy ENUM(
    'step_based',      -- แบ่งตาม denoising steps
    'tile_based',      -- แบ่งตาม image tiles
    'batch_based',     -- แบ่งตาม batch items
    'hybrid'           -- ผสมผสาน
) DEFAULT 'tile_based';

ALTER TABLE render_jobs ADD COLUMN parallel_config JSON;
-- Example: {
--   "total_steps": 100,
--   "tile_size": 512,
--   "overlap": 64,
--   "blend_mode": "linear"
-- }

ALTER TABLE render_jobs ADD COLUMN assembly_status ENUM(
    'pending',         -- รอ chunks เสร็จ
    'assembling',      -- กำลังประกอบ
    'completed',       -- เสร็จแล้ว
    'failed'           -- ล้มเหลว
) DEFAULT 'pending';

ALTER TABLE render_jobs ADD COLUMN final_result_url VARCHAR(500);
```

### job_chunks table (เพิ่ม fields)

```sql
ALTER TABLE job_chunks ADD COLUMN chunk_type ENUM(
    'step_range',      -- ช่วง steps (e.g., 1-25)
    'tile',            -- tile ของรูป
    'batch_item',      -- item ใน batch
    'latent_pass'      -- latent intermediate
) DEFAULT 'tile';

ALTER TABLE job_chunks ADD COLUMN chunk_config JSON;
-- For step_range: {"start_step": 1, "end_step": 25, "total_steps": 100}
-- For tile: {"x": 0, "y": 0, "width": 512, "height": 512, "overlap": 64}
-- For batch_item: {"batch_index": 0, "seed": 12345}

ALTER TABLE job_chunks ADD COLUMN depends_on_chunk_id VARCHAR(64) NULL;
-- สำหรับ step-based ที่ต้องรอ chunk ก่อนหน้า

ALTER TABLE job_chunks ADD COLUMN partial_result_url VARCHAR(500);
-- URL ของผลลัพธ์บางส่วน (latent หรือ tile image)

ALTER TABLE job_chunks ADD COLUMN partial_result_data LONGBLOB;
-- Binary data สำหรับ latent tensors (ถ้าไม่ใช้ URL)
```

---

## New Services

### 1. ChunkingService - แบ่งงาน

```php
<?php
// app/Services/ChunkingService.php

class ChunkingService
{
    public function chunkJob(RenderJob $job): array
    {
        return match($job->chunking_strategy) {
            'step_based' => $this->chunkBySteps($job),
            'tile_based' => $this->chunkByTiles($job),
            'batch_based' => $this->chunkByBatch($job),
            'hybrid' => $this->chunkHybrid($job),
        };
    }

    private function chunkByTiles(RenderJob $job): array
    {
        $config = $job->parallel_config;
        $width = $config['width'];
        $height = $config['height'];
        $tileSize = $config['tile_size'] ?? 512;
        $overlap = $config['overlap'] ?? 64;

        $chunks = [];
        $index = 0;

        for ($y = 0; $y < $height; $y += ($tileSize - $overlap)) {
            for ($x = 0; $x < $width; $x += ($tileSize - $overlap)) {
                $chunks[] = [
                    'chunk_id' => "{$job->job_id}_tile_{$index}",
                    'chunk_type' => 'tile',
                    'chunk_index' => $index,
                    'chunk_config' => [
                        'x' => $x,
                        'y' => $y,
                        'width' => min($tileSize, $width - $x),
                        'height' => min($tileSize, $height - $y),
                        'overlap' => $overlap,
                    ],
                    'depends_on_chunk_id' => null, // Tiles are independent
                ];
                $index++;
            }
        }

        return $chunks;
    }

    private function chunkBySteps(RenderJob $job): array
    {
        $config = $job->parallel_config;
        $totalSteps = $config['total_steps'];
        $numChunks = $config['num_chunks'] ?? 4;
        $stepsPerChunk = ceil($totalSteps / $numChunks);

        $chunks = [];
        $prevChunkId = null;

        for ($i = 0; $i < $numChunks; $i++) {
            $startStep = $i * $stepsPerChunk + 1;
            $endStep = min(($i + 1) * $stepsPerChunk, $totalSteps);

            $chunkId = "{$job->job_id}_steps_{$i}";

            $chunks[] = [
                'chunk_id' => $chunkId,
                'chunk_type' => 'step_range',
                'chunk_index' => $i,
                'chunk_config' => [
                    'start_step' => $startStep,
                    'end_step' => $endStep,
                    'total_steps' => $totalSteps,
                ],
                'depends_on_chunk_id' => $prevChunkId,
            ];

            $prevChunkId = $chunkId;
        }

        return $chunks;
    }
}
```

### 2. JobAssemblerService - รวมผลลัพธ์

```php
<?php
// app/Services/JobAssemblerService.php

class JobAssemblerService
{
    public function checkAndAssemble(RenderJob $job): bool
    {
        // ตรวจสอบว่า chunks ทั้งหมดเสร็จหรือยัง
        $allCompleted = $job->chunks()
            ->where('status', '!=', 'completed')
            ->count() === 0;

        if (!$allCompleted) {
            return false;
        }

        // เริ่มประกอบ
        $job->update(['assembly_status' => 'assembling']);

        try {
            $result = match($job->chunking_strategy) {
                'tile_based' => $this->assembleTiles($job),
                'step_based' => $this->assembleSteps($job),
                'batch_based' => $this->assembleBatch($job),
                'hybrid' => $this->assembleHybrid($job),
            };

            $job->update([
                'assembly_status' => 'completed',
                'final_result_url' => $result['url'],
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Notify customer
            $this->notifyJobCompleted($job);

            return true;
        } catch (\Exception $e) {
            $job->update(['assembly_status' => 'failed']);
            return false;
        }
    }

    private function assembleTiles(RenderJob $job): array
    {
        $chunks = $job->chunks()->orderBy('chunk_index')->get();
        $config = $job->parallel_config;

        // สร้าง canvas ขนาดเต็ม
        $finalImage = imagecreatetruecolor($config['width'], $config['height']);

        foreach ($chunks as $chunk) {
            $tileConfig = $chunk->chunk_config;
            $tileImage = $this->loadImage($chunk->partial_result_url);

            // วาง tile ลงบน canvas (พร้อม blend overlap)
            $this->blendTile(
                $finalImage,
                $tileImage,
                $tileConfig['x'],
                $tileConfig['y'],
                $tileConfig['overlap'],
                $config['blend_mode'] ?? 'linear'
            );

            imagedestroy($tileImage);
        }

        // Save final image
        $outputPath = "results/{$job->job_id}_final.png";
        imagepng($finalImage, storage_path("app/public/{$outputPath}"));
        imagedestroy($finalImage);

        return ['url' => asset("storage/{$outputPath}")];
    }

    private function blendTile($canvas, $tile, $x, $y, $overlap, $blendMode): void
    {
        $tileWidth = imagesx($tile);
        $tileHeight = imagesy($tile);

        for ($ty = 0; $ty < $tileHeight; $ty++) {
            for ($tx = 0; $tx < $tileWidth; $tx++) {
                $canvasX = $x + $tx;
                $canvasY = $y + $ty;

                // คำนวณ blend weight สำหรับ overlap zone
                $weight = $this->calculateBlendWeight(
                    $tx, $ty, $tileWidth, $tileHeight, $overlap, $blendMode
                );

                if ($weight >= 1.0) {
                    // ไม่ต้อง blend
                    $color = imagecolorat($tile, $tx, $ty);
                    imagesetpixel($canvas, $canvasX, $canvasY, $color);
                } else {
                    // Blend กับ pixel เดิม
                    $existingColor = imagecolorat($canvas, $canvasX, $canvasY);
                    $newColor = imagecolorat($tile, $tx, $ty);
                    $blendedColor = $this->blendColors($existingColor, $newColor, $weight);
                    imagesetpixel($canvas, $canvasX, $canvasY, $blendedColor);
                }
            }
        }
    }
}
```

---

## Updated API Flow

### Client Workflow (Worker Node)

```
┌──────────────────────────────────────────────────────────────────┐
│                    PARALLEL WORKER FLOW                          │
└──────────────────────────────────────────────────────────────────┘

  1. GET /jobs/work?node_id=XXX
     │
     │  Response includes chunk_type and dependencies
     │
     ▼
  ┌─────────────────────────────────────────────┐
  │ {                                           │
  │   "chunk_id": "job_123_tile_5",             │
  │   "chunk_type": "tile",                     │
  │   "chunk_config": {                         │
  │     "x": 512, "y": 0,                       │
  │     "width": 512, "height": 512,            │
  │     "overlap": 64                           │
  │   },                                        │
  │   "job_params": {                           │
  │     "prompt": "sunset over mountains",      │
  │     "model": "sdxl",                        │
  │     "base_image_url": "..." // if needed    │
  │   }                                         │
  │ }                                           │
  └─────────────────────────────────────────────┘
     │
     │  2. Process the tile/chunk
     │
     ▼
  3. POST /jobs/submit
     │
     │  Upload partial result
     │
     ▼
  ┌─────────────────────────────────────────────┐
  │ {                                           │
  │   "chunk_id": "job_123_tile_5",             │
  │   "result_hash": "abc123...",               │
  │   "partial_result_url": "https://...",      │
  │   "metadata": {                             │
  │     "processing_time": 12.5,                │
  │     "actual_tile_size": [512, 512]          │
  │   }                                         │
  │ }                                           │
  └─────────────────────────────────────────────┘
     │
     │  Server checks if all chunks done
     │  If yes → Trigger assembly
     │
     ▼
  4. Assembly happens on server
     │
     ▼
  5. Customer receives final image
```

### Step-based with Dependencies

```
┌──────────────────────────────────────────────────────────────────┐
│               STEP-BASED WITH DEPENDENCIES                       │
└──────────────────────────────────────────────────────────────────┘

  Chunk 0 (Steps 1-25): depends_on = null
       │
       │ เสร็จแล้ว upload latent
       ▼
  Chunk 1 (Steps 26-50): depends_on = chunk_0
       │
       │ Server marks as "ready" เมื่อ chunk_0 เสร็จ
       │ Worker download latent จาก chunk_0
       ▼
  Chunk 2 (Steps 51-75): depends_on = chunk_1
       │
       ▼
  Chunk 3 (Steps 76-100): depends_on = chunk_2
       │
       ▼
  Final image (no assembly needed, chunk_3 output is final)
```

---

## Performance Comparison

### Scenario: Generate 4K image (4096x4096) with 50 steps

**Old System (Single Node):**
```
1 Node (RTX 3060):
- Time: 180 seconds
- Memory: 12GB VRAM (barely fits)
```

**New System (Parallel):**
```
16 Nodes (Mixed GPUs):
- Split: 16 tiles of 1024x1024
- Time per tile: ~12 seconds
- Total time: ~15 seconds (with overhead)
- Speedup: 12x faster!
```

### Scenario: Generate 100-step Diffusion

**Old System:**
```
1 Node (RTX 3060):
- Time: 60 seconds
```

**New System (4 Nodes):**
```
Step-based chunking:
- Node A: Steps 1-25 (15s)
- Node B: Steps 26-50 (15s) - waits for A
- Node C: Steps 51-75 (15s) - waits for B
- Node D: Steps 76-100 (15s) - waits for C
- Total: 60s (same, due to dependencies)

Better approach - Tile + Steps hybrid:
- Generate at 512x512, upscale to 1024x1024
- 4 tiles × 25 steps each
- Total: ~20 seconds
```

---

## Configuration Options

```php
// config/gpu_pool.php

return [
    'chunking' => [
        'default_strategy' => 'tile_based',

        'tile_based' => [
            'default_tile_size' => 512,
            'min_tile_size' => 256,
            'max_tile_size' => 1024,
            'default_overlap' => 64,
            'blend_mode' => 'linear', // linear, gaussian, none
        ],

        'step_based' => [
            'min_steps_per_chunk' => 10,
            'max_chunks' => 10,
        ],

        'batch_based' => [
            'max_items_per_chunk' => 4,
        ],
    ],

    'assembly' => [
        'auto_assemble' => true,
        'assembly_timeout' => 300, // 5 minutes
        'retry_failed_chunks' => true,
        'max_chunk_retries' => 3,
    ],

    'distribution' => [
        'prefer_fast_nodes_for_dependencies' => true,
        'min_nodes_for_parallel' => 2,
        'max_nodes_per_job' => 16,
    ],
];
```

---

## Summary

| Feature | Old System | New System |
|---------|------------|------------|
| Distribution | 1 Job → 1 Node | 1 Job → N Nodes |
| Parallelism | None | True parallel (tiles) |
| Large images | Slow/OOM | Fast, distributed |
| Fault tolerance | Retry whole job | Retry single chunk |
| Speed | Limited by slowest | Sum of all nodes |
| Complexity | Simple | More complex |

**เหมาะสำหรับ:**
- High-resolution image generation (4K+)
- Batch generation (multiple variations)
- Time-sensitive jobs
- Utilizing all available GPU resources
