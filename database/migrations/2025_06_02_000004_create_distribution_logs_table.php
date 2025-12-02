<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('render_job_id')->nullable()->constrained()->onDelete('set null');

            // Job info
            $table->string('job_id', 50)->index();
            $table->string('job_type', 50)->nullable();
            $table->string('job_priority', 20)->default('normal');

            // Distribution result
            $table->string('status', 20); // success, no_workers, failed
            $table->string('strategy', 50)->nullable(); // single_worker, parallel_balanced, etc.
            $table->string('reason')->nullable(); // English reason
            $table->string('reason_th')->nullable(); // Thai reason

            // Decision factors (JSON)
            $table->json('factors')->nullable();

            // Assignment info
            $table->unsignedInteger('chunks_created')->default(0);
            $table->unsignedInteger('nodes_assigned')->default(0);
            $table->json('assignment_details')->nullable();

            // Analysis data (for debugging/ML)
            $table->json('job_analysis')->nullable();
            $table->json('resource_analysis')->nullable();

            // Performance
            $table->unsignedInteger('decision_time_ms')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('strategy');
            $table->index('status');
            $table->index(['created_at', 'strategy']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_logs');
    }
};
