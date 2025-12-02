<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpu_node_id')->constrained()->onDelete('cascade');

            // Evaluation period
            $table->timestamp('evaluation_period_start');
            $table->timestamp('evaluation_period_end');

            // Job statistics
            $table->unsignedInteger('total_jobs')->default(0);
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->unsignedInteger('failed_jobs')->default(0);

            // Scores
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('speed_score', 5, 2)->default(0);
            $table->decimal('reliability_score', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);

            // Rank
            $table->string('rank', 20);
            $table->string('previous_rank', 20)->nullable();
            $table->boolean('rank_changed')->default(false);

            // Detailed metrics (JSON)
            $table->json('metrics_detail')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['gpu_node_id', 'created_at']);
            $table->index('rank_changed');
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_performance_logs');
    }
};
