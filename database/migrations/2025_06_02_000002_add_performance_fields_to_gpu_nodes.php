<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            // Performance scores
            $table->decimal('performance_score', 5, 2)->default(50)->after('benchmark_score');
            $table->string('performance_rank', 20)->default('novice')->after('performance_score');
            $table->decimal('success_rate', 5, 2)->default(0)->after('performance_rank');
            $table->decimal('speed_score', 5, 2)->default(50)->after('success_rate');
            $table->decimal('reliability_score', 5, 2)->default(50)->after('speed_score');
            $table->decimal('quality_score', 5, 2)->default(50)->after('reliability_score');

            // Statistics
            $table->decimal('avg_completion_time', 10, 2)->default(0)->after('quality_score');
            $table->unsignedInteger('total_completed_chunks')->default(0)->after('avg_completion_time');
            $table->decimal('total_earnings', 15, 2)->default(0)->after('total_completed_chunks');
            $table->decimal('total_uptime_hours', 10, 2)->default(0)->after('total_earnings');

            // Evaluation tracking
            $table->timestamp('last_evaluation_at')->nullable()->after('total_uptime_hours');

            // Indexes
            $table->index('performance_score');
            $table->index('performance_rank');
            $table->index(['performance_score', 'hashrate']);
        });
    }

    public function down(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->dropIndex(['performance_score']);
            $table->dropIndex(['performance_rank']);
            $table->dropIndex(['performance_score', 'hashrate']);

            $table->dropColumn([
                'performance_score',
                'performance_rank',
                'success_rate',
                'speed_score',
                'reliability_score',
                'quality_score',
                'avg_completion_time',
                'total_completed_chunks',
                'total_earnings',
                'total_uptime_hours',
                'last_evaluation_at',
            ]);
        });
    }
};
