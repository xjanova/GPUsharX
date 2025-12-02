<?php

namespace App\Console\Commands;

use App\Models\GpuNode;
use App\Services\WorkerPerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EvaluateWorkerPerformance extends Command
{
    protected $signature = 'workers:evaluate
                            {--node= : Evaluate specific node by node_id}
                            {--all : Force evaluate all workers}';

    protected $description = 'Evaluate worker performance and update ranks';

    protected WorkerPerformanceService $performanceService;

    public function __construct(WorkerPerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    public function handle(): int
    {
        $startTime = microtime(true);

        if ($nodeId = $this->option('node')) {
            // Evaluate specific node
            $node = GpuNode::where('node_id', $nodeId)->first();

            if (!$node) {
                $this->error("Node not found: {$nodeId}");
                return self::FAILURE;
            }

            $this->info("Evaluating node: {$nodeId}");
            $result = $this->performanceService->evaluateWorker($node);
            $this->displayResult($result);

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            // Force evaluate all workers
            $this->info('Evaluating all workers...');
            $results = $this->performanceService->evaluateAllWorkers();
        } else {
            // Only evaluate workers that need it
            $nodes = GpuNode::where('is_verified', true)
                ->where(function ($q) {
                    $q->whereNull('last_evaluation_at')
                        ->orWhere('last_evaluation_at', '<', now()->subHours(6));
                })
                ->get();

            if ($nodes->isEmpty()) {
                $this->info('No workers need evaluation at this time.');
                return self::SUCCESS;
            }

            $this->info("Evaluating {$nodes->count()} workers...");
            $results = [];
            $bar = $this->output->createProgressBar($nodes->count());

            foreach ($nodes as $node) {
                $results[] = $this->performanceService->evaluateWorker($node);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        // Summary
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $rankChanges = collect($results)->where('rank_changed', true)->count();

        $this->newLine();
        $this->info("=== Evaluation Summary ===");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Workers Evaluated', count($results)],
                ['Rank Changes', $rankChanges],
                ['Duration', "{$duration}ms"],
            ]
        );

        // Show rank changes
        if ($rankChanges > 0) {
            $this->newLine();
            $this->info("Rank Changes:");
            foreach ($results as $result) {
                if ($result['rank_changed']) {
                    $direction = $result['rank'] > $result['previous_rank'] ? '↑' : '↓';
                    $this->line("  {$direction} {$result['node_name']}: {$result['previous_rank']} → {$result['rank']}");
                }
            }
        }

        Log::info("Worker evaluation completed", [
            'workers_evaluated' => count($results),
            'rank_changes' => $rankChanges,
            'duration_ms' => $duration,
        ]);

        return self::SUCCESS;
    }

    protected function displayResult(array $result): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Node', $result['node_name']],
                ['Overall Score', round($result['overall_score'], 2)],
                ['Rank', $result['rank']],
                ['Previous Rank', $result['previous_rank']],
                ['Rank Changed', $result['rank_changed'] ? 'Yes' : 'No'],
                ['Success Rate', $result['metrics']['success_rate'] . '%'],
                ['Speed Score', $result['metrics']['speed_score']],
                ['Reliability Score', $result['metrics']['reliability_score']],
                ['Quality Score', $result['metrics']['quality_score']],
                ['Total Jobs', $result['metrics']['total_jobs']],
                ['Completed', $result['metrics']['completed_jobs']],
                ['Failed', $result['metrics']['failed_jobs']],
            ]
        );
    }
}
