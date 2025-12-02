<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NodeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'gpu_node_id',
        'session_token',
        'started_at',
        'ended_at',
        'total_work_seconds',
        'chunks_completed',
        'credits_earned',
        'ip_address',
        'system_info',
        'is_valid',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'system_info' => 'array',
        'credits_earned' => 'decimal:8',
        'is_valid' => 'boolean',
    ];

    public function gpuNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class);
    }

    public function verificationTasks(): HasMany
    {
        return $this->hasMany(VerificationTask::class);
    }

    public static function startSession(GpuNode $node, string $ipAddress, array $systemInfo = []): self
    {
        // End any existing sessions
        self::where('gpu_node_id', $node->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        return self::create([
            'gpu_node_id' => $node->id,
            'session_token' => Str::random(64),
            'started_at' => now(),
            'ip_address' => $ipAddress,
            'system_info' => $systemInfo,
        ]);
    }

    public function endSession(): void
    {
        $this->update([
            'ended_at' => now(),
        ]);
    }

    public function addWorkTime(int $seconds): void
    {
        $this->increment('total_work_seconds', $seconds);
    }

    public function addCompletedChunk(float $credits): void
    {
        $this->increment('chunks_completed');
        $this->increment('credits_earned', $credits);
    }

    public function flagAsInvalid(): void
    {
        $this->update(['is_valid' => false]);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
