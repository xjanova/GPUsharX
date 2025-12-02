<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'render_job_id',
        'job_id',
        'job_type',
        'job_priority',
        'status',
        'strategy',
        'reason',
        'reason_th',
        'factors',
        'chunks_created',
        'nodes_assigned',
        'assignment_details',
        'job_analysis',
        'resource_analysis',
        'decision_time_ms',
    ];

    protected $casts = [
        'factors' => 'array',
        'assignment_details' => 'array',
        'job_analysis' => 'array',
        'resource_analysis' => 'array',
    ];

    public function renderJob(): BelongsTo
    {
        return $this->belongsTo(RenderJob::class);
    }

    /**
     * Scope: by strategy
     */
    public function scopeByStrategy($query, string $strategy)
    {
        return $query->where('strategy', $strategy);
    }

    /**
     * Scope: successful distributions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
