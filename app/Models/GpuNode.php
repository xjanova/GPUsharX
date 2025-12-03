<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GpuNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'node_id',
        'machine_id',
        'name',
        'gpu_model',
        'gpu_name',
        'gpu_vram_mb',
        'benchmark_score',
        'hashrate',
        'status',
        'ip_address',
        'client_version',
        'gpu_specs',
        'is_verified',
        'last_heartbeat',
        'last_benchmark',
        // Performance fields
        'performance_score',
        'performance_rank',
        'success_rate',
        'speed_score',
        'reliability_score',
        'quality_score',
        'avg_completion_time',
        'total_completed_chunks',
        'total_earnings',
        'total_uptime_hours',
        'last_evaluation_at',
        // Model management
        'installed_models',
    ];

    protected $casts = [
        'gpu_specs' => 'array',
        'installed_models' => 'array',
        'last_heartbeat' => 'datetime',
        'last_benchmark' => 'datetime',
        'last_evaluation_at' => 'datetime',
        'hashrate' => 'decimal:4',
        'performance_score' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'speed_score' => 'decimal:2',
        'reliability_score' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'avg_completion_time' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_uptime_hours' => 'decimal:2',
        'is_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobChunks(): HasMany
    {
        return $this->hasMany(JobChunk::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NodeSession::class);
    }

    public function verificationTasks(): HasMany
    {
        return $this->hasMany(VerificationTask::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function performanceLogs(): HasMany
    {
        return $this->hasMany(WorkerPerformanceLog::class);
    }

    public function isOnline(): bool
    {
        if (!$this->last_heartbeat) {
            return false;
        }
        return $this->last_heartbeat->diffInSeconds(now()) < 60;
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online')
            ->where('last_heartbeat', '>', now()->subMinutes(2));
    }

    public function scopeAvailableForWork($query)
    {
        return $query->whereIn('status', ['online', 'idle'])
            ->where('last_heartbeat', '>', now()->subMinutes(2));
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRank($query, string $rank)
    {
        return $query->where('performance_rank', $rank);
    }

    public function scopeTopPerformers($query, int $limit = 10)
    {
        return $query->orderBy('performance_score', 'desc')->limit($limit);
    }

    /**
     * Filter nodes by minimum VRAM
     */
    public function scopeWithMinVram($query, int $minVramMb)
    {
        return $query->where('gpu_vram_mb', '>=', $minVramMb);
    }

    /**
     * Filter nodes by VRAM tier
     */
    public function scopeByVramTier($query, string $tier)
    {
        $tiers = [
            '3gb' => [3000, 3999],
            '4gb' => [4000, 5999],
            '6gb' => [6000, 7999],
            '8gb' => [8000, 11999],
            '12gb' => [12000, 23999],
            '24gb' => [24000, 999999],
        ];

        if (!isset($tiers[$tier])) {
            return $query;
        }

        [$min, $max] = $tiers[$tier];
        return $query->whereBetween('gpu_vram_mb', [$min, $max]);
    }

    /**
     * Filter nodes that have a specific model installed
     */
    public function scopeWithModel($query, string $modelId)
    {
        return $query->where(function ($q) use ($modelId) {
            $q->whereNull('installed_models')
              ->orWhereJsonContains('installed_models', $modelId);
        });
    }

    /**
     * Check if node can handle a job with given VRAM requirement
     */
    public function canHandleVram(int $requiredVramMb): bool
    {
        return ($this->gpu_vram_mb ?? 0) >= $requiredVramMb;
    }

    /**
     * Check if node has a specific model installed
     */
    public function hasModel(string $modelId): bool
    {
        $installed = $this->installed_models ?? [];
        return empty($installed) || in_array($modelId, $installed);
    }

    /**
     * Get VRAM tier name
     */
    public function getVramTierAttribute(): string
    {
        $vram = $this->gpu_vram_mb ?? 0;

        if ($vram >= 24000) return '24gb+';
        if ($vram >= 12000) return '12gb';
        if ($vram >= 8000) return '8gb';
        if ($vram >= 6000) return '6gb';
        if ($vram >= 4000) return '4gb';
        if ($vram >= 3000) return '3gb';
        return 'low';
    }

    /**
     * Increment completed chunks and update stats
     */
    public function recordCompletedChunk(float $earnings, int $completionTimeSeconds): void
    {
        $this->increment('total_completed_chunks');
        $this->increment('total_earnings', $earnings);

        // Update average completion time (rolling average)
        $totalChunks = $this->total_completed_chunks;
        $currentAvg = $this->avg_completion_time ?? 0;
        $newAvg = (($currentAvg * ($totalChunks - 1)) + $completionTimeSeconds) / $totalChunks;

        $this->update(['avg_completion_time' => $newAvg]);
    }

    /**
     * Update uptime hours
     */
    public function addUptimeHours(float $hours): void
    {
        $this->increment('total_uptime_hours', $hours);
    }

    /**
     * Check if needs evaluation
     */
    public function needsEvaluation(): bool
    {
        if (!$this->last_evaluation_at) {
            return true;
        }
        // Evaluate every 6 hours
        return $this->last_evaluation_at->diffInHours(now()) >= 6;
    }

    /**
     * Get rank display info
     */
    public function getRankInfoAttribute(): array
    {
        $ranks = [
            'legendary' => ['name' => 'Legendary', 'color' => '#FFD700', 'icon' => 'crown'],
            'master' => ['name' => 'Master', 'color' => '#9B59B6', 'icon' => 'star'],
            'expert' => ['name' => 'Expert', 'color' => '#3498DB', 'icon' => 'certificate'],
            'skilled' => ['name' => 'Skilled', 'color' => '#2ECC71', 'icon' => 'tools'],
            'apprentice' => ['name' => 'Apprentice', 'color' => '#95A5A6', 'icon' => 'user'],
            'novice' => ['name' => 'Novice', 'color' => '#BDC3C7', 'icon' => 'seedling'],
        ];

        return $ranks[$this->performance_rank ?? 'novice'] ?? $ranks['novice'];
    }
}
