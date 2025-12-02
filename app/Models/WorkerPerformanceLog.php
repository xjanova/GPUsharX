<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerPerformanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gpu_node_id',
        'evaluation_period_start',
        'evaluation_period_end',
        'total_jobs',
        'completed_jobs',
        'failed_jobs',
        'success_rate',
        'speed_score',
        'reliability_score',
        'quality_score',
        'overall_score',
        'rank',
        'previous_rank',
        'rank_changed',
        'metrics_detail',
    ];

    protected $casts = [
        'evaluation_period_start' => 'datetime',
        'evaluation_period_end' => 'datetime',
        'rank_changed' => 'boolean',
        'metrics_detail' => 'array',
    ];

    public function gpuNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class);
    }

    /**
     * Scope: rank changes only
     */
    public function scopeRankChanged($query)
    {
        return $query->where('rank_changed', true);
    }

    /**
     * Scope: by node
     */
    public function scopeForNode($query, int $nodeId)
    {
        return $query->where('gpu_node_id', $nodeId);
    }

    /**
     * Scope: recent evaluations
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
