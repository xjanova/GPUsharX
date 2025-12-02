<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpuNode;
use App\Models\NodeSession;
use App\Models\VerificationTask;
use App\Services\AntiCheatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NodeController extends Controller
{
    public function __construct(
        protected AntiCheatService $antiCheatService
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gpu_model' => 'required|string|max:255',
            'gpu_vram_mb' => 'required|integer|min:2048',
            'machine_id' => 'required|string|max:255',
            'gpu_specs' => 'nullable|array',
            'client_version' => 'required|string|max:50',
        ]);

        $user = $request->user();

        // Anti-cheat validation
        $validation = $this->antiCheatService->validateNodeRegistration([
            'machine_id' => $validated['machine_id'],
            'gpu_vram_mb' => $validated['gpu_vram_mb'],
            'gpu_specs' => $validated['gpu_specs'] ?? [],
        ]);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Node registration rejected',
                'errors' => $validation['issues'],
            ], 422);
        }

        // Check if node already exists
        $existingNode = GpuNode::where('machine_id', $validated['machine_id'])->first();
        if ($existingNode) {
            if ($existingNode->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This machine is already registered to another account',
                ], 409);
            }

            // Update existing node
            $existingNode->update([
                'gpu_model' => $validated['gpu_model'],
                'gpu_vram_mb' => $validated['gpu_vram_mb'],
                'gpu_specs' => $validated['gpu_specs'] ?? null,
                'client_version' => $validated['client_version'],
                'status' => 'online',
                'last_heartbeat' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Node updated and reconnected',
                'data' => [
                    'node_id' => $existingNode->node_id,
                    'status' => $existingNode->status,
                ],
            ]);
        }

        // Create new node
        $node = GpuNode::create([
            'user_id' => $user->id,
            'node_id' => 'NODE-' . strtoupper(Str::random(12)),
            'machine_id' => $validated['machine_id'],
            'gpu_model' => $validated['gpu_model'],
            'gpu_vram_mb' => $validated['gpu_vram_mb'],
            'gpu_specs' => $validated['gpu_specs'] ?? null,
            'client_version' => $validated['client_version'],
            'ip_address' => $request->ip(),
            'status' => 'online',
            'last_heartbeat' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Node registered successfully',
            'data' => [
                'node_id' => $node->node_id,
                'status' => $node->status,
                'benchmark_required' => true,
            ],
        ], 201);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'status' => 'nullable|in:online,idle,working',
            'gpu_temp' => 'nullable|integer',
            'gpu_usage' => 'nullable|integer|min:0|max:100',
            'memory_usage' => 'nullable|integer|min:0|max:100',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($node->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Node is banned',
            ], 403);
        }

        // Validate heartbeat
        if (!$this->antiCheatService->validateHeartbeat($node, $validated)) {
            return response()->json([
                'success' => false,
                'message' => 'Heartbeat validation failed',
            ], 422);
        }

        $updateData = [
            'last_heartbeat' => now(),
            'ip_address' => $request->ip(),
        ];

        if (isset($validated['status']) && $node->status !== 'working') {
            $updateData['status'] = $validated['status'];
        }

        if (isset($validated['gpu_specs'])) {
            $specs = $node->gpu_specs ?? [];
            $specs['last_metrics'] = [
                'temp' => $validated['gpu_temp'] ?? null,
                'gpu_usage' => $validated['gpu_usage'] ?? null,
                'memory_usage' => $validated['memory_usage'] ?? null,
                'timestamp' => now()->toIso8601String(),
            ];
            $updateData['gpu_specs'] = $specs;
        }

        $node->update($updateData);

        // Check if verification needed
        $verificationTask = null;
        if ($this->antiCheatService->shouldVerifyNode($node)) {
            $verificationTask = $this->antiCheatService->createRandomVerification($node);
            $verificationTask->send();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $node->status,
                'verification_task' => $verificationTask ? [
                    'id' => $verificationTask->id,
                    'type' => $verificationTask->task_type,
                    'params' => $verificationTask->task_params,
                    'time_limit' => $verificationTask->time_limit_seconds,
                ] : null,
            ],
        ]);
    }

    public function submitBenchmark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'benchmark_score' => 'required|integer|min:0',
            'benchmark_details' => 'nullable|array',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Calculate hashrate from benchmark score
        $hashrate = $validated['benchmark_score'] / 100; // Simplified conversion

        $node->update([
            'benchmark_score' => $validated['benchmark_score'],
            'hashrate' => $hashrate,
            'last_benchmark' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Benchmark submitted successfully',
            'data' => [
                'benchmark_score' => $node->benchmark_score,
                'hashrate' => $node->hashrate,
            ],
        ]);
    }

    public function submitVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
            'task_id' => 'required|integer|exists:verification_tasks,id',
            'result_hash' => 'required|string',
            'time_taken' => 'required|integer|min:0',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $task = VerificationTask::where('id', $validated['task_id'])
            ->where('gpu_node_id', $node->id)
            ->where('status', 'sent')
            ->firstOrFail();

        $isValid = $task->complete($validated['result_hash'], $validated['time_taken']);

        $this->antiCheatService->processVerificationResult($task);

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => $isValid,
                'message' => $isValid ? 'Verification passed' : 'Verification failed',
            ],
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string|exists:gpu_nodes,node_id',
        ]);

        $node = GpuNode::where('node_id', $validated['node_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $node->update(['status' => 'offline']);

        // End active session
        NodeSession::where('gpu_node_id', $node->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Node disconnected',
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $nodes = $request->user()->gpuNodes()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'nodes' => $nodes->map(fn($node) => [
                    'id' => $node->id,
                    'node_id' => $node->node_id,
                    'gpu_model' => $node->gpu_model,
                    'gpu_vram_mb' => $node->gpu_vram_mb,
                    'benchmark_score' => $node->benchmark_score,
                    'hashrate' => $node->hashrate,
                    'status' => $node->status,
                    'client_version' => $node->client_version,
                    'last_heartbeat' => $node->last_heartbeat?->toIso8601String(),
                    'last_benchmark' => $node->last_benchmark?->toIso8601String(),
                    'is_online' => $node->isOnline(),
                ]),
            ],
        ]);
    }
}
