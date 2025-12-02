<?php

namespace App\Console\Commands;

use App\Models\RenderJob;
use App\Services\SmartDistributionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DistributeJobs extends Command
{
    protected $signature = 'jobs:distribute
                            {--job= : Distribute specific job by job_id}
                            {--limit=10 : Maximum jobs to distribute}';

    protected $description = 'Intelligently distribute pending jobs to workers';

    protected SmartDistributionService $distributionService;

    public function __construct(SmartDistributionService $distributionService)
    {
        parent::__construct();
        $this->distributionService = $distributionService;
    }

    public function handle(): int
    {
        $startTime = microtime(true);

        if ($jobId = $this->option('job')) {
            // Distribute specific job
            $job = RenderJob::where('job_id', $jobId)->first();

            if (!$job) {
                $this->error("Job not found: {$jobId}");
                return self::FAILURE;
            }

            if (!in_array($job->status, ['pending', 'queued'])) {
                $this->error("Job is not pending/queued: {$job->status}");
                return self::FAILURE;
            }

            $this->info("Distributing job: {$jobId}");
            $result = $this->distributionService->distributeJob($job);
            $this->displayResult($result);

            return self::SUCCESS;
        }

        // Get pending jobs
        $limit = (int) $this->option('limit');
        $pendingJobs = RenderJob::whereIn('status', ['pending', 'queued'])
            ->where(function ($q) {
                $q->whereNull('chunking_strategy')
                    ->orWhere('total_chunks', 0);
            })
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($pendingJobs->isEmpty()) {
            $this->info('No pending jobs to distribute.');
            return self::SUCCESS;
        }

        $this->info("Distributing {$pendingJobs->count()} jobs...");
        $this->newLine();

        $results = [];
        $totalChunks = 0;
        $totalNodes = 0;

        foreach ($pendingJobs as $job) {
            $this->line("Processing: {$job->job_id} ({$job->type})");

            $result = $this->distributionService->distributeJob($job);
            $results[] = $result;

            $totalChunks += $result['chunks_created'] ?? 0;
            $totalNodes += $result['nodes_assigned'] ?? 0;

            // Show strategy used
            $strategy = $result['strategy'] ?? 'unknown';
            $reason = $result['reason_th'] ?? $result['reason'] ?? '';
            $this->line("  → Strategy: {$strategy}");
            $this->line("  → Reason: {$reason}");
            $this->line("  → Chunks: {$result['chunks_created']}, Nodes: {$result['nodes_assigned']}");
            $this->newLine();
        }

        // Summary
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->info("=== Distribution Summary ===");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Jobs Distributed', count($results)],
                ['Total Chunks Created', $totalChunks],
                ['Total Nodes Assigned', $totalNodes],
                ['Duration', "{$duration}ms"],
            ]
        );

        // Strategy breakdown
        $strategyCount = collect($results)->groupBy('strategy')->map->count();
        $this->newLine();
        $this->info("Strategies Used:");
        foreach ($strategyCount as $strategy => $count) {
            $this->line("  - {$strategy}: {$count}");
        }

        Log::info("Job distribution completed", [
            'jobs_distributed' => count($results),
            'total_chunks' => $totalChunks,
            'total_nodes' => $totalNodes,
            'duration_ms' => $duration,
        ]);

        return self::SUCCESS;
    }

    protected function displayResult(array $result): void
    {
        $this->newLine();
        $this->info("Distribution Result:");
        $this->table(
            ['Field', 'Value'],
            [
                ['Strategy', $result['strategy'] ?? 'N/A'],
                ['Reason', $result['reason'] ?? 'N/A'],
                ['Reason (TH)', $result['reason_th'] ?? 'N/A'],
                ['Chunks Created', $result['chunks_created'] ?? 0],
                ['Nodes Assigned', $result['nodes_assigned'] ?? 0],
                ['Log ID', $result['log_id'] ?? 'N/A'],
            ]
        );

        if (!empty($result['assignment'])) {
            $this->newLine();
            $this->info("Assignment Details:");
            $this->table(
                ['Chunk ID', 'Node ID', 'Node Name', 'Workload'],
                collect($result['assignment'])->map(fn($a) => [
                    $a['chunk_id'],
                    $a['node_id'],
                    $a['node_name'],
                    $a['workload'],
                ])->toArray()
            );
        }
    }
}
