<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pool-wide statistics for dashboard
        Schema::create('pool_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('total_nodes')->default(0);
            $table->integer('active_nodes')->default(0);
            $table->decimal('total_hashrate', 20, 4)->default(0);
            $table->integer('jobs_completed')->default(0);
            $table->integer('chunks_processed')->default(0);
            $table->decimal('credits_distributed', 20, 8)->default(0);
            $table->decimal('platform_revenue', 20, 8)->default(0);
            $table->integer('new_users')->default(0);
            $table->timestamps();

            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_statistics');
    }
};
