<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For anti-cheat: random verification tasks to validate GPU is actually working
        Schema::create('verification_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpu_node_id')->constrained()->onDelete('cascade');
            $table->foreignId('node_session_id')->nullable()->constrained()->onDelete('set null');
            $table->string('task_type'); // benchmark, proof_of_work, result_verification
            $table->json('task_params');
            $table->string('expected_result_hash')->nullable();
            $table->string('actual_result_hash')->nullable();
            $table->enum('status', ['pending', 'sent', 'completed', 'failed', 'timeout'])->default('pending');
            $table->boolean('is_valid')->nullable();
            $table->integer('time_limit_seconds')->default(60);
            $table->integer('actual_time_seconds')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['gpu_node_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_tasks');
    }
};
