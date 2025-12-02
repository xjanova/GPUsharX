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
}
