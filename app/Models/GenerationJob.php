<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GenerationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'ai_model_id',
        'type',
        'prompt',
        'negative_prompt',
        'params',
        'status',
        'progress',
        'result_url',
        'result_thumbnail',
        'result_metadata',
        'credits_used',
        'processing_time_ms',
        'processed_by_node',
        'error_message',
        'visibility',
        'likes',
    ];

    protected $casts = [
        'params' => 'array',
        'result_metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->job_id) {
                $model->job_id = 'GEN-' . strtoupper(Str::random(12));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function processedByNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class, 'processed_by_node');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }
}
