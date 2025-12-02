<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'gpu_node_id',
        'node_session_id',
        'task_type',
        'task_params',
        'expected_result_hash',
        'actual_result_hash',
        'status',
        'is_valid',
        'time_limit_seconds',
        'actual_time_seconds',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'task_params' => 'array',
        'is_valid' => 'boolean',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function gpuNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class);
    }

    public function nodeSession(): BelongsTo
    {
        return $this->belongsTo(NodeSession::class);
    }

    public static function createBenchmarkTask(GpuNode $node, NodeSession $session = null): self
    {
        $seed = random_int(1000, 9999);

        return self::create([
            'gpu_node_id' => $node->id,
            'node_session_id' => $session?->id,
            'task_type' => 'benchmark',
            'task_params' => [
                'seed' => $seed,
                'iterations' => 1000,
                'matrix_size' => 1024,
            ],
            'time_limit_seconds' => 120,
            'status' => 'pending',
        ]);
    }

    public static function createProofOfWork(GpuNode $node, NodeSession $session = null): self
    {
        $challenge = bin2hex(random_bytes(16));

        return self::create([
            'gpu_node_id' => $node->id,
            'node_session_id' => $session?->id,
            'task_type' => 'proof_of_work',
            'task_params' => [
                'challenge' => $challenge,
                'difficulty' => 4, // Leading zeros required
            ],
            'time_limit_seconds' => 60,
            'status' => 'pending',
        ]);
    }

    public function send(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function complete(string $resultHash, int $timeTaken): bool
    {
        $this->update([
            'actual_result_hash' => $resultHash,
            'actual_time_seconds' => $timeTaken,
            'completed_at' => now(),
        ]);

        // Validate result
        $isValid = $this->validateResult($resultHash, $timeTaken);

        $this->update([
            'status' => 'completed',
            'is_valid' => $isValid,
        ]);

        if (!$isValid && $this->nodeSession) {
            $this->nodeSession->flagAsInvalid();
        }

        return $isValid;
    }

    public function timeout(): void
    {
        $this->update([
            'status' => 'timeout',
            'is_valid' => false,
        ]);

        if ($this->nodeSession) {
            $this->nodeSession->flagAsInvalid();
        }
    }

    protected function validateResult(string $resultHash, int $timeTaken): bool
    {
        // Check time limit
        if ($timeTaken > $this->time_limit_seconds) {
            return false;
        }

        // For benchmark tasks, check if result matches expected
        if ($this->expected_result_hash && $resultHash !== $this->expected_result_hash) {
            return false;
        }

        // For proof of work, verify the hash meets difficulty
        if ($this->task_type === 'proof_of_work') {
            $difficulty = $this->task_params['difficulty'] ?? 4;
            $prefix = str_repeat('0', $difficulty);
            if (!str_starts_with($resultHash, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
