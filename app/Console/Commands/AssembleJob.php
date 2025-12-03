<?php

namespace App\Console\Commands;

use App\Models\RenderJob;
use App\Services\JobAssemblerService;
use Illuminate\Console\Command;

class AssembleJob extends Command
{
    protected $signature = 'job:assemble {job_id?}';
    protected $description = 'Manually trigger job assembly';

    public function handle(JobAssemblerService $assemblerService)
    {
        $jobId = $this->argument('job_id');

        if ($jobId) {
            $job = RenderJob::where('job_id', $jobId)->first();
            if (!$job) {
                $this->error("Job not found: {$jobId}");
                return 1;
            }

            $this->info("Assembling job: {$job->job_id}");
            $result = $assemblerService->assemble($job);

            if ($result) {
                $this->info("Assembly completed successfully!");
                $job->refresh();
                $this->info("Result URL: {$job->final_result_url}");
            } else {
                $this->error("Assembly failed!");
            }

            return $result ? 0 : 1;
        }

        // Assemble all ready jobs
        $readyJobs = RenderJob::where('assembly_status', 'ready')
            ->orWhere('assembly_status', 'assembling')
            ->get();

        if ($readyJobs->isEmpty()) {
            $this->info("No jobs ready for assembly");
            return 0;
        }

        foreach ($readyJobs as $job) {
            $this->info("Assembling: {$job->job_id}");
            $result = $assemblerService->assemble($job);
            $this->info($result ? "  -> Success" : "  -> Failed");
        }

        return 0;
    }
}
