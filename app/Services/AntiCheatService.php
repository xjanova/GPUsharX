<?php

namespace App\Services;

use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\NodeSession;
use App\Models\VerificationTask;
use Illuminate\Support\Facades\Log;

class AntiCheatService
{
    protected array $suspiciousPatterns = [];

    public function validateNodeRegistration(array $systemInfo, ?int $userId = null): array
    {
        $issues = [];

        // Check if machine_id already exists with different user
        if (isset($systemInfo['machine_id'])) {
            $existingNode = GpuNode::where('machine_id', $systemInfo['machine_id'])->first();
            if ($existingNode && $userId && $existingNode->user_id !== $userId) {
                $issues[] = 'Machine ID already registered to another user';
            }
            // If same user, allow reconnection (no issue added)
        }

        // Validate GPU info is realistic
        if (isset($systemInfo['gpu_vram_mb'])) {
            $vram = $systemInfo['gpu_vram_mb'];
            if ($vram < 2048 || $vram > 128000) {
                $issues[] = 'Invalid VRAM amount reported';
            }
        }

        // Check for VM/emulator indicators
        if ($this->detectVirtualMachine($systemInfo)) {
            $issues[] = 'Virtual machine detected';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    public function validateHeartbeat(GpuNode $node, array $heartbeatData): bool
    {
        // Check if system info matches registered info
        if (isset($heartbeatData['gpu_model']) && $heartbeatData['gpu_model'] !== $node->gpu_model) {
            $this->flagSuspiciousActivity($node, 'GPU model mismatch');
            return false;
        }

        // Check for impossible timestamp patterns
        if ($node->last_heartbeat) {
            $timeDiff = now()->diffInSeconds($node->last_heartbeat);
            if ($timeDiff < 5) {
                // Too frequent heartbeats might indicate manipulation
                $this->flagSuspiciousActivity($node, 'Abnormally frequent heartbeats');
            }
        }

        return true;
    }

    public function validateWorkResult(JobChunk $chunk, string $resultHash, array $metadata): array
    {
        $issues = [];

        // Check completion time is realistic
        if ($chunk->started_at) {
            $completionTime = $chunk->started_at->diffInSeconds(now());
            $minExpectedTime = 5; // Minimum 5 seconds

            if ($completionTime < $minExpectedTime) {
                $issues[] = 'Completion time unrealistically fast';
            }
        }

        // Verify result hash format
        if (!preg_match('/^[a-f0-9]{64}$/', $resultHash)) {
            $issues[] = 'Invalid result hash format';
        }

        // Cross-reference with verification tasks
        if ($chunk->gpuNode) {
            $failedVerifications = VerificationTask::where('gpu_node_id', $chunk->gpuNode->id)
                ->where('is_valid', false)
                ->where('created_at', '>', now()->subHours(24))
                ->count();

            if ($failedVerifications > 3) {
                $issues[] = 'Node has multiple failed verifications';
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'confidence' => empty($issues) ? 1.0 : 0.5,
        ];
    }

    public function createRandomVerification(GpuNode $node): VerificationTask
    {
        $taskTypes = ['benchmark', 'proof_of_work'];
        $type = $taskTypes[array_rand($taskTypes)];

        $session = NodeSession::where('gpu_node_id', $node->id)
            ->whereNull('ended_at')
            ->first();

        return match ($type) {
            'benchmark' => VerificationTask::createBenchmarkTask($node, $session),
            'proof_of_work' => VerificationTask::createProofOfWork($node, $session),
            default => VerificationTask::createProofOfWork($node, $session),
        };
    }

    public function shouldVerifyNode(GpuNode $node): bool
    {
        // Random chance of verification (5%)
        if (random_int(1, 100) <= 5) {
            return true;
        }

        // Always verify if no recent verification
        $lastVerification = VerificationTask::where('gpu_node_id', $node->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$lastVerification || $lastVerification->created_at->diffInHours(now()) > 4) {
            return true;
        }

        // Check if node has suspicious activity
        $suspiciousCount = $this->getSuspiciousActivityCount($node);
        if ($suspiciousCount > 0) {
            return true;
        }

        return false;
    }

    public function processVerificationResult(VerificationTask $task): void
    {
        if (!$task->is_valid) {
            $this->flagSuspiciousActivity(
                $task->gpuNode,
                "Failed verification task: {$task->task_type}"
            );

            // Check if should ban
            $failCount = VerificationTask::where('gpu_node_id', $task->gpu_node_id)
                ->where('is_valid', false)
                ->where('created_at', '>', now()->subHours(24))
                ->count();

            if ($failCount >= 5) {
                $this->banNode($task->gpuNode, 'Multiple failed verifications');
            }
        }
    }

    protected function detectVirtualMachine(array $systemInfo): bool
    {
        $vmIndicators = [
            'vmware',
            'virtualbox',
            'hyper-v',
            'qemu',
            'xen',
            'parallels',
        ];

        $systemString = strtolower(json_encode($systemInfo));

        foreach ($vmIndicators as $indicator) {
            if (str_contains($systemString, $indicator)) {
                return true;
            }
        }

        return false;
    }

    protected function flagSuspiciousActivity(GpuNode $node, string $reason): void
    {
        Log::warning("Suspicious activity detected for node {$node->node_id}: {$reason}");

        // You could implement a suspicious_activities table to track this
        // For now, just log it
    }

    protected function getSuspiciousActivityCount(GpuNode $node): int
    {
        // Count failed verifications as suspicious activity indicator
        return VerificationTask::where('gpu_node_id', $node->id)
            ->where('is_valid', false)
            ->where('created_at', '>', now()->subHours(24))
            ->count();
    }

    protected function banNode(GpuNode $node, string $reason): void
    {
        $node->update(['status' => 'banned']);

        Log::warning("Node {$node->node_id} banned: {$reason}");

        // End active session
        NodeSession::where('gpu_node_id', $node->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now(), 'is_valid' => false]);
    }

    public function generateMachineId(array $hardwareInfo): string
    {
        $components = [
            $hardwareInfo['cpu_id'] ?? '',
            $hardwareInfo['motherboard_serial'] ?? '',
            $hardwareInfo['disk_serial'] ?? '',
            $hardwareInfo['gpu_uuid'] ?? '',
        ];

        return hash('sha256', implode('|', $components));
    }
}
