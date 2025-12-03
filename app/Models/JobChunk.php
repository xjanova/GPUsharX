<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'chunk_id',
        'render_job_id',
        'gpu_node_id',
        'chunk_index',
        'status',
        'chunk_params',
        'progress',
        'credits_earned',
        'result_hash',
        'result_file',
        'retry_count',
        'error_message',
        'assigned_at',
        'started_at',
        'completed_at',
        // Parallel Processing fields
        'chunk_type',
        'chunk_config',
        'depends_on_chunk_id',
        'dependency_status',
        'workload_weight',
        'partial_result_url',
        'required_vram_mb', // VRAM requirement for this specific chunk
    ];

    protected $casts = [
        'chunk_params' => 'array',
        'chunk_config' => 'array',
        'workload_weight' => 'float',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function renderJob(): BelongsTo
    {
        return $this->belongsTo(RenderJob::class);
    }

    public function gpuNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(JobChunk::class, 'depends_on_chunk_id');
    }

    public function dependentChunks(): HasMany
    {
        return $this->hasMany(JobChunk::class, 'depends_on_chunk_id');
    }

    public function assignToNode(GpuNode $node): void
    {
        $this->update([
            'gpu_node_id' => $node->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $node->update(['status' => 'working']);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(string $resultHash, string $resultFile = null, string $partialResultUrl = null): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'result_hash' => $resultHash,
            'result_file' => $resultFile,
            'partial_result_url' => $partialResultUrl,
            'completed_at' => now(),
        ]);

        // Update parent job
        $job = $this->renderJob;
        $job->increment('completed_chunks');

        // Unlock dependent chunks
        $this->unlockDependentChunks();

        // Set node back to idle (but keep it available for more chunks)
        if ($this->gpuNode) {
            $this->gpuNode->update(['status' => 'idle']);
        }

        // Check if ready for assembly (all chunks completed)
        if ($job->completed_chunks >= $job->total_chunks) {
            // Don't mark job as completed yet - wait for assembly
            $job->update([
                'assembly_status' => 'ready',
            ]);
        }
    }

    /**
     * Unlock chunks that depend on this chunk
     */
    public function unlockDependentChunks(): void
    {
        $this->dependentChunks()
            ->where('dependency_status', 'waiting')
            ->update([
                'dependency_status' => 'ready',
                'status' => 'pending',
            ]);
    }

    /**
     * Save partial result (for parallel processing)
     */
    public function savePartialResult(string $url, array $metadata = []): void
    {
        $this->update([
            'partial_result_url' => $url,
            'chunk_config' => array_merge($this->chunk_config ?? [], ['result_metadata' => $metadata]),
        ]);
    }

    /**
     * Check if chunk dependencies are satisfied
     */
    public function isDependencySatisfied(): bool
    {
        if (!$this->depends_on_chunk_id) {
            return true;
        }

        $dependency = $this->dependsOn;
        return $dependency && $dependency->status === 'completed';
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->increment('retry_count');

        if ($this->retry_count >= 3) {
            $this->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);
        } else {
            // Reset for retry
            $this->update([
                'status' => 'pending',
                'gpu_node_id' => null,
                'error_message' => $errorMessage,
                'assigned_at' => null,
                'started_at' => null,
            ]);
        }

        if ($this->gpuNode) {
            $this->gpuNode->update(['status' => 'idle']);
        }
    }

    public function scopePending($query)
    {
        return $query->where('job_chunks.status', 'pending');
    }

    public function scopeAssigned($query)
    {
        return $query->where('job_chunks.status', 'assigned');
    }
}
