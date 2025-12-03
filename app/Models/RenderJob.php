<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenderJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'estimated_credits',
        'actual_credits',
        'total_chunks',
        'completed_chunks',
        'required_vram_mb',
        'estimated_time_seconds',
        'job_params',
        'input_file',
        'output_file',
        'created_by',
        'started_at',
        'completed_at',
        // Parallel Processing fields
        'chunking_strategy',
        'parallel_config',
        'assembly_status',
        'assembly_started_at',
        'assembly_completed_at',
        'final_result_url',
    ];

    protected $casts = [
        'job_params' => 'array',
        'parallel_config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'assembly_started_at' => 'datetime',
        'assembly_completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(JobChunk::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_chunks === 0) {
            return 0;
        }
        return round(($this->completed_chunks / $this->total_chunks) * 100, 2);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function splitIntoChunks(int $numChunks = null): void
    {
        if ($numChunks === null) {
            // Auto-determine chunks based on job complexity
            $numChunks = max(1, ceil($this->estimated_credits / 100));
        }

        $creditsPerChunk = floor($this->estimated_credits / $numChunks);

        for ($i = 0; $i < $numChunks; $i++) {
            JobChunk::create([
                'chunk_id' => $this->job_id . '_chunk_' . $i,
                'render_job_id' => $this->id,
                'chunk_index' => $i,
                'status' => 'pending',
                'dependency_status' => 'ready', // Mark as ready for workers to pick up
                'chunk_params' => [
                    'index' => $i,
                    'total' => $numChunks,
                    'base_params' => $this->job_params,
                ],
                'credits_earned' => $creditsPerChunk,
            ]);
        }

        $this->update([
            'total_chunks' => $numChunks,
            'status' => 'queued',
        ]);
    }

    /**
     * VRAM Tier Configuration
     * Defines capabilities and limitations for each VRAM tier
     */
    public const VRAM_TIERS = [
        'ultra_low' => [
            'min_mb' => 2048,    // 2GB
            'max_mb' => 3072,    // 3GB
            'max_resolution' => 384,
            'max_steps_per_chunk' => 5,
            'supported_models' => ['sd_1.5_quantized', 'tiny_sd'],
            'precision' => 'fp16',
            'label' => 'Ultra Low (2-3GB)',
        ],
        'very_low' => [
            'min_mb' => 3073,    // 3GB+
            'max_mb' => 4096,    // 4GB
            'max_resolution' => 512,
            'max_steps_per_chunk' => 8,
            'supported_models' => ['sd_1.5', 'sd_1.5_quantized'],
            'precision' => 'fp16',
            'label' => 'Very Low (3-4GB)',
        ],
        'low' => [
            'min_mb' => 4097,    // 4GB+
            'max_mb' => 6144,    // 6GB
            'max_resolution' => 640,
            'max_steps_per_chunk' => 12,
            'supported_models' => ['sd_1.5', 'sd_2.1'],
            'precision' => 'fp16',
            'label' => 'Low (4-6GB)',
        ],
        'medium' => [
            'min_mb' => 6145,    // 6GB+
            'max_mb' => 8192,    // 8GB
            'max_resolution' => 768,
            'max_steps_per_chunk' => 20,
            'supported_models' => ['sd_1.5', 'sd_2.1', 'sdxl_base'],
            'precision' => 'fp16',
            'label' => 'Medium (6-8GB)',
        ],
        'high' => [
            'min_mb' => 8193,    // 8GB+
            'max_mb' => 12288,   // 12GB
            'max_resolution' => 1024,
            'max_steps_per_chunk' => 30,
            'supported_models' => ['sd_1.5', 'sd_2.1', 'sdxl', 'sdxl_turbo'],
            'precision' => 'fp16',
            'label' => 'High (8-12GB)',
        ],
        'ultra' => [
            'min_mb' => 12289,   // 12GB+
            'max_mb' => 999999,
            'max_resolution' => 2048,
            'max_steps_per_chunk' => 50,
            'supported_models' => ['all'],
            'precision' => 'fp32',
            'label' => 'Ultra (12GB+)',
        ],
    ];

    /**
     * Get VRAM tier for a given amount of VRAM
     */
    public static function getVramTier(int $vramMb): array
    {
        foreach (self::VRAM_TIERS as $tierName => $tier) {
            if ($vramMb >= $tier['min_mb'] && $vramMb <= $tier['max_mb']) {
                return array_merge(['name' => $tierName], $tier);
            }
        }
        return array_merge(['name' => 'ultra'], self::VRAM_TIERS['ultra']);
    }

    /**
     * Smart chunking based on available workers' VRAM
     * Supports ALL VRAM levels including 3GB and below
     */
    public function smartSplit(): void
    {
        $params = $this->job_params ?? [];
        $width = $params['width'] ?? 1024;
        $height = $params['height'] ?? 1024;
        $steps = $params['steps'] ?? 30;
        $requiredVram = $this->required_vram_mb ?? 8192;

        // Determine best chunking strategy
        $strategy = $this->determineChunkingStrategy($requiredVram, $width, $height, $steps);

        $this->update([
            'chunking_strategy' => $strategy['type'],
            'parallel_config' => $strategy['config'],
        ]);

        // Create chunks based on strategy
        match ($strategy['type']) {
            'tile_based' => $this->createTileChunks($strategy['config']),
            'step_based' => $this->createStepChunks($strategy['config']),
            'hybrid' => $this->createHybridChunks($strategy['config']),
            'micro' => $this->createMicroChunks($strategy['config']),
            default => $this->splitIntoChunks(1),
        };
    }

    /**
     * Determine the best chunking strategy based on job requirements
     * Now supports ultra-low VRAM (3GB and below)
     */
    private function determineChunkingStrategy(int $requiredVram, int $width, int $height, int $steps): array
    {
        // Check what workers are available
        $availableWorkers = GpuNode::where('status', 'online')
            ->where('is_verified', true)
            ->get();

        $minVram = $availableWorkers->min('gpu_vram_mb') ?? 8192;
        $maxVram = $availableWorkers->max('gpu_vram_mb') ?? 8192;
        $minTier = self::getVramTier($minVram);

        // If all workers have enough VRAM, no need to split
        if ($minVram >= $requiredVram) {
            return [
                'type' => 'single',
                'config' => ['chunks' => 1],
            ];
        }

        // ULTRA LOW VRAM (2-3GB) - Use micro chunking
        if ($minVram <= 3072) {
            return $this->createMicroStrategy($requiredVram, $width, $height, $steps, $minVram);
        }

        // VERY LOW VRAM (3-4GB) - Aggressive step splitting + small tiles
        if ($minVram <= 4096) {
            return $this->createVeryLowVramStrategy($requiredVram, $width, $height, $steps, $minVram);
        }

        // LOW VRAM (4-6GB) - Step splitting or tiles
        if ($minVram <= 6144) {
            return $this->createLowVramStrategy($requiredVram, $width, $height, $steps, $minVram);
        }

        // Large image (> 1024x1024) - use tile-based
        if ($width > 1024 || $height > 1024) {
            $tilesX = ceil($width / 512);
            $tilesY = ceil($height / 512);
            $tileVram = $this->estimateVramForTile(512, 512, $requiredVram, $width, $height);

            return [
                'type' => 'tile_based',
                'config' => [
                    'tiles_x' => $tilesX,
                    'tiles_y' => $tilesY,
                    'tile_width' => 512,
                    'tile_height' => 512,
                    'overlap' => 64,
                    'width' => $width,
                    'height' => $height,
                    'vram_per_tile' => $tileVram,
                    'blend_mode' => 'linear',
                ],
            ];
        }

        // Standard image with many steps - use step-based
        if ($steps > 15 && $minVram < $requiredVram) {
            $vramRatio = $requiredVram / max($minVram, 4096);
            $numStepChunks = ceil($vramRatio);
            $stepsPerChunk = ceil($steps / $numStepChunks);

            return [
                'type' => 'step_based',
                'config' => [
                    'total_steps' => $steps,
                    'num_chunks' => $numStepChunks,
                    'steps_per_chunk' => $stepsPerChunk,
                    'vram_per_chunk' => intval($requiredVram / $numStepChunks),
                    'sequential' => true,
                ],
            ];
        }

        // Hybrid approach for complex jobs
        if ($requiredVram > 12000) {
            return [
                'type' => 'hybrid',
                'config' => [
                    'tiles_x' => 2,
                    'tiles_y' => 2,
                    'step_chunks' => 2,
                    'width' => $width,
                    'height' => $height,
                    'steps' => $steps,
                ],
            ];
        }

        // Default: single chunk
        return [
            'type' => 'single',
            'config' => ['chunks' => 1],
        ];
    }

    /**
     * Strategy for ULTRA LOW VRAM (2-3GB)
     * Uses micro tiles (256x256) + aggressive step splitting
     */
    private function createMicroStrategy(int $requiredVram, int $width, int $height, int $steps, int $availableVram): array
    {
        // For 3GB: use 256x256 tiles, max 5 steps per chunk
        $tileSize = 256;
        $maxStepsPerChunk = 5;
        $overlap = 32;

        $tilesX = ceil($width / ($tileSize - $overlap));
        $tilesY = ceil($height / ($tileSize - $overlap));
        $stepChunks = ceil($steps / $maxStepsPerChunk);

        // Estimate VRAM per micro chunk
        $vramPerChunk = $this->estimateVramForMicroChunk($tileSize, $maxStepsPerChunk, $availableVram);

        return [
            'type' => 'micro',
            'config' => [
                'tiles_x' => $tilesX,
                'tiles_y' => $tilesY,
                'tile_size' => $tileSize,
                'overlap' => $overlap,
                'step_chunks' => $stepChunks,
                'steps_per_chunk' => $maxStepsPerChunk,
                'total_steps' => $steps,
                'width' => $width,
                'height' => $height,
                'vram_per_chunk' => $vramPerChunk,
                'use_fp16' => true,
                'use_attention_slicing' => true,
                'use_vae_tiling' => true,
            ],
        ];
    }

    /**
     * Strategy for VERY LOW VRAM (3-4GB)
     */
    private function createVeryLowVramStrategy(int $requiredVram, int $width, int $height, int $steps, int $availableVram): array
    {
        $tileSize = 384;
        $maxStepsPerChunk = 8;
        $overlap = 48;

        // Reduce resolution if needed
        $effectiveWidth = min($width, 512);
        $effectiveHeight = min($height, 512);

        $tilesX = ceil($effectiveWidth / ($tileSize - $overlap));
        $tilesY = ceil($effectiveHeight / ($tileSize - $overlap));
        $stepChunks = ceil($steps / $maxStepsPerChunk);

        $vramPerChunk = min(3500, intval($availableVram * 0.85));

        return [
            'type' => 'hybrid',
            'config' => [
                'tiles_x' => max(1, $tilesX),
                'tiles_y' => max(1, $tilesY),
                'tile_width' => $tileSize,
                'tile_height' => $tileSize,
                'step_chunks' => $stepChunks,
                'steps_per_chunk' => $maxStepsPerChunk,
                'total_steps' => $steps,
                'width' => $effectiveWidth,
                'height' => $effectiveHeight,
                'original_width' => $width,
                'original_height' => $height,
                'overlap' => $overlap,
                'vram_per_chunk' => $vramPerChunk,
                'use_fp16' => true,
                'use_attention_slicing' => true,
                'upscale_after' => $width > 512 || $height > 512,
            ],
        ];
    }

    /**
     * Strategy for LOW VRAM (4-6GB)
     */
    private function createLowVramStrategy(int $requiredVram, int $width, int $height, int $steps, int $availableVram): array
    {
        $tileSize = 512;
        $maxStepsPerChunk = 12;
        $overlap = 64;

        if ($width <= 512 && $height <= 512) {
            // Small image - just split steps
            $stepChunks = ceil($requiredVram / $availableVram);
            $stepsPerChunk = ceil($steps / $stepChunks);

            return [
                'type' => 'step_based',
                'config' => [
                    'total_steps' => $steps,
                    'num_chunks' => $stepChunks,
                    'steps_per_chunk' => min($stepsPerChunk, $maxStepsPerChunk),
                    'vram_per_chunk' => min(5500, intval($availableVram * 0.9)),
                    'sequential' => true,
                    'use_fp16' => true,
                ],
            ];
        }

        // Larger image - use tiles
        $tilesX = ceil($width / ($tileSize - $overlap));
        $tilesY = ceil($height / ($tileSize - $overlap));

        return [
            'type' => 'tile_based',
            'config' => [
                'tiles_x' => $tilesX,
                'tiles_y' => $tilesY,
                'tile_width' => $tileSize,
                'tile_height' => $tileSize,
                'overlap' => $overlap,
                'width' => $width,
                'height' => $height,
                'vram_per_tile' => min(5500, intval($availableVram * 0.9)),
                'blend_mode' => 'linear',
                'use_fp16' => true,
            ],
        ];
    }

    /**
     * Estimate VRAM needed for a tile
     */
    private function estimateVramForTile(int $tileW, int $tileH, int $fullVram, int $fullW, int $fullH): int
    {
        $tileRatio = ($tileW * $tileH) / ($fullW * $fullH);
        // VRAM scales roughly with image area, but with overhead
        return intval($fullVram * $tileRatio * 0.6 + 2048);
    }

    /**
     * Estimate VRAM for micro chunk (ultra-low VRAM)
     */
    private function estimateVramForMicroChunk(int $tileSize, int $stepsPerChunk, int $availableVram): int
    {
        // For 256x256 with 5 steps, estimate ~2.5GB
        $baseVram = 2048;
        $perPixelFactor = ($tileSize * $tileSize) / (256 * 256);
        $perStepFactor = $stepsPerChunk / 5;

        $estimated = intval($baseVram * $perPixelFactor * sqrt($perStepFactor));
        return min($estimated, intval($availableVram * 0.8));
    }

    /**
     * Create tile-based chunks for large images
     */
    private function createTileChunks(array $config): void
    {
        $tilesX = $config['tiles_x'];
        $tilesY = $config['tiles_y'];
        $tileW = $config['tile_width'];
        $tileH = $config['tile_height'];
        $overlap = $config['overlap'] ?? 64;
        $totalChunks = $tilesX * $tilesY;

        $creditsPerChunk = floor(($this->estimated_credits ?? 10) / $totalChunks);

        $index = 0;
        for ($y = 0; $y < $tilesY; $y++) {
            for ($x = 0; $x < $tilesX; $x++) {
                $startX = $x * ($tileW - $overlap);
                $startY = $y * ($tileH - $overlap);

                JobChunk::create([
                    'chunk_id' => $this->job_id . '_tile_' . $x . '_' . $y,
                    'render_job_id' => $this->id,
                    'chunk_index' => $index,
                    'status' => 'pending',
                    'dependency_status' => 'ready', // Tiles can run in parallel
                    'required_vram_mb' => $config['vram_per_tile'] ?? 4096,
                    'chunk_params' => [
                        'type' => 'tile',
                        'tile_x' => $x,
                        'tile_y' => $y,
                        'start_x' => $startX,
                        'start_y' => $startY,
                        'width' => $tileW,
                        'height' => $tileH,
                        'overlap' => $overlap,
                        'base_params' => $this->job_params,
                    ],
                    'chunk_config' => [
                        'x' => $startX,
                        'y' => $startY,
                        'width' => $tileW,
                        'height' => $tileH,
                    ],
                    'credits_earned' => $creditsPerChunk,
                ]);

                $index++;
            }
        }

        $this->update([
            'total_chunks' => $totalChunks,
            'status' => 'queued',
        ]);
    }

    /**
     * Create step-based chunks for low VRAM workers
     * Each chunk processes a range of denoising steps
     */
    private function createStepChunks(array $config): void
    {
        $totalSteps = $config['total_steps'];
        $numChunks = $config['num_chunks'];
        $stepsPerChunk = $config['steps_per_chunk'];

        $creditsPerChunk = floor(($this->estimated_credits ?? 10) / $numChunks);

        for ($i = 0; $i < $numChunks; $i++) {
            $startStep = $i * $stepsPerChunk;
            $endStep = min(($i + 1) * $stepsPerChunk, $totalSteps);

            JobChunk::create([
                'chunk_id' => $this->job_id . '_steps_' . $startStep . '_' . $endStep,
                'render_job_id' => $this->id,
                'chunk_index' => $i,
                'status' => 'pending',
                // First chunk is ready, others depend on previous
                'dependency_status' => $i === 0 ? 'ready' : 'waiting',
                'depends_on_chunk_id' => $i > 0 ? $this->job_id . '_steps_' . (($i - 1) * $stepsPerChunk) . '_' . $startStep : null,
                'required_vram_mb' => $config['vram_per_chunk'] ?? 4096,
                'chunk_params' => [
                    'type' => 'step_range',
                    'start_step' => $startStep,
                    'end_step' => $endStep,
                    'total_steps' => $totalSteps,
                    'is_first' => $i === 0,
                    'is_last' => $i === $numChunks - 1,
                    'base_params' => $this->job_params,
                ],
                'credits_earned' => $creditsPerChunk,
            ]);
        }

        $this->update([
            'total_chunks' => $numChunks,
            'status' => 'queued',
        ]);
    }

    /**
     * Create hybrid chunks (tiles + steps)
     */
    private function createHybridChunks(array $config): void
    {
        $tilesX = $config['tiles_x'] ?? 2;
        $tilesY = $config['tiles_y'] ?? 2;
        $stepChunks = $config['step_chunks'] ?? 2;
        $width = $config['width'] ?? 1024;
        $height = $config['height'] ?? 1024;
        $steps = $config['steps'] ?? 30;

        $tileW = ceil($width / $tilesX);
        $tileH = ceil($height / $tilesY);
        $stepsPerChunk = ceil($steps / $stepChunks);
        $overlap = 64;

        $totalChunks = $tilesX * $tilesY * $stepChunks;
        $creditsPerChunk = floor(($this->estimated_credits ?? 10) / $totalChunks);

        $index = 0;
        for ($ty = 0; $ty < $tilesY; $ty++) {
            for ($tx = 0; $tx < $tilesX; $tx++) {
                for ($s = 0; $s < $stepChunks; $s++) {
                    $startX = $tx * ($tileW - $overlap);
                    $startY = $ty * ($tileH - $overlap);
                    $startStep = $s * $stepsPerChunk;
                    $endStep = min(($s + 1) * $stepsPerChunk, $steps);

                    // First step chunk of each tile is ready, others wait
                    $dependsOn = null;
                    $depStatus = 'ready';
                    if ($s > 0) {
                        $depStatus = 'waiting';
                        $prevS = $s - 1;
                        $prevStartStep = $prevS * $stepsPerChunk;
                        $prevEndStep = min(($prevS + 1) * $stepsPerChunk, $steps);
                        $dependsOn = $this->job_id . '_hybrid_' . $tx . '_' . $ty . '_' . $prevStartStep . '_' . $prevEndStep;
                    }

                    JobChunk::create([
                        'chunk_id' => $this->job_id . '_hybrid_' . $tx . '_' . $ty . '_' . $startStep . '_' . $endStep,
                        'render_job_id' => $this->id,
                        'chunk_index' => $index,
                        'status' => 'pending',
                        'dependency_status' => $depStatus,
                        'depends_on_chunk_id' => $dependsOn,
                        'required_vram_mb' => 4096, // Optimized for low VRAM
                        'chunk_params' => [
                            'type' => 'hybrid',
                            'tile_x' => $tx,
                            'tile_y' => $ty,
                            'start_x' => $startX,
                            'start_y' => $startY,
                            'tile_width' => $tileW,
                            'tile_height' => $tileH,
                            'start_step' => $startStep,
                            'end_step' => $endStep,
                            'total_steps' => $steps,
                            'is_first_step' => $s === 0,
                            'is_last_step' => $s === $stepChunks - 1,
                            'base_params' => $this->job_params,
                        ],
                        'chunk_config' => [
                            'x' => $startX,
                            'y' => $startY,
                            'width' => $tileW,
                            'height' => $tileH,
                        ],
                        'credits_earned' => $creditsPerChunk,
                    ]);

                    $index++;
                }
            }
        }

        $this->update([
            'total_chunks' => $totalChunks,
            'status' => 'queued',
        ]);
    }

    /**
     * Create micro chunks for ULTRA LOW VRAM (2-3GB)
     * Maximum fragmentation for minimal VRAM usage
     */
    private function createMicroChunks(array $config): void
    {
        $tilesX = $config['tiles_x'];
        $tilesY = $config['tiles_y'];
        $tileSize = $config['tile_size'];
        $overlap = $config['overlap'];
        $stepChunks = $config['step_chunks'];
        $stepsPerChunk = $config['steps_per_chunk'];
        $totalSteps = $config['total_steps'];
        $width = $config['width'];
        $height = $config['height'];

        $totalChunks = $tilesX * $tilesY * $stepChunks;
        $creditsPerChunk = max(1, floor(($this->estimated_credits ?? 10) / $totalChunks));

        $index = 0;
        for ($ty = 0; $ty < $tilesY; $ty++) {
            for ($tx = 0; $tx < $tilesX; $tx++) {
                for ($s = 0; $s < $stepChunks; $s++) {
                    $startX = max(0, $tx * ($tileSize - $overlap));
                    $startY = max(0, $ty * ($tileSize - $overlap));
                    $startStep = $s * $stepsPerChunk;
                    $endStep = min(($s + 1) * $stepsPerChunk, $totalSteps);

                    // First step chunk of each tile is ready, others wait
                    $dependsOn = null;
                    $depStatus = 'ready';
                    if ($s > 0) {
                        $depStatus = 'waiting';
                        $prevS = $s - 1;
                        $prevStartStep = $prevS * $stepsPerChunk;
                        $prevEndStep = min(($prevS + 1) * $stepsPerChunk, $totalSteps);
                        $dependsOn = $this->job_id . '_micro_' . $tx . '_' . $ty . '_' . $prevStartStep . '_' . $prevEndStep;
                    }

                    JobChunk::create([
                        'chunk_id' => $this->job_id . '_micro_' . $tx . '_' . $ty . '_' . $startStep . '_' . $endStep,
                        'render_job_id' => $this->id,
                        'chunk_index' => $index,
                        'chunk_type' => 'micro',
                        'status' => 'pending',
                        'dependency_status' => $depStatus,
                        'depends_on_chunk_id' => $dependsOn,
                        'required_vram_mb' => $config['vram_per_chunk'] ?? 2560, // ~2.5GB
                        'chunk_params' => [
                            'type' => 'micro',
                            'tile_x' => $tx,
                            'tile_y' => $ty,
                            'start_x' => $startX,
                            'start_y' => $startY,
                            'tile_width' => $tileSize,
                            'tile_height' => $tileSize,
                            'start_step' => $startStep,
                            'end_step' => $endStep,
                            'total_steps' => $totalSteps,
                            'is_first_step' => $s === 0,
                            'is_last_step' => $s === $stepChunks - 1,
                            'original_width' => $width,
                            'original_height' => $height,
                            // Optimization flags for low VRAM
                            'use_fp16' => $config['use_fp16'] ?? true,
                            'use_attention_slicing' => $config['use_attention_slicing'] ?? true,
                            'use_vae_tiling' => $config['use_vae_tiling'] ?? true,
                            'base_params' => $this->job_params,
                        ],
                        'chunk_config' => [
                            'x' => $startX,
                            'y' => $startY,
                            'width' => $tileSize,
                            'height' => $tileSize,
                        ],
                        'credits_earned' => $creditsPerChunk,
                    ]);

                    $index++;
                }
            }
        }

        $this->update([
            'total_chunks' => $totalChunks,
            'status' => 'queued',
        ]);
    }

    /**
     * Get minimum VRAM required for any chunk of this job
     */
    public function getMinChunkVramAttribute(): int
    {
        return $this->chunks()->min('required_vram_mb') ?? $this->required_vram_mb ?? 8192;
    }

    /**
     * Get statistics about chunks by VRAM tier
     */
    public function getChunksByVramTier(): array
    {
        $chunks = $this->chunks;
        $tiers = [];

        foreach ($chunks as $chunk) {
            $vram = $chunk->required_vram_mb ?? $this->required_vram_mb ?? 8192;
            $tier = self::getVramTier($vram);
            $tierName = $tier['name'];

            if (!isset($tiers[$tierName])) {
                $tiers[$tierName] = [
                    'label' => $tier['label'],
                    'count' => 0,
                    'completed' => 0,
                ];
            }

            $tiers[$tierName]['count']++;
            if ($chunk->status === 'completed') {
                $tiers[$tierName]['completed']++;
            }
        }

        return $tiers;
    }
}
