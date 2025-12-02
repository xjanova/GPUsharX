<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('chunk_id')->unique();
            $table->foreignId('render_job_id')->constrained()->onDelete('cascade');
            $table->foreignId('gpu_node_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('chunk_index'); // Which part of the job
            $table->enum('status', ['pending', 'assigned', 'processing', 'completed', 'failed', 'verification'])->default('pending');
            $table->json('chunk_params')->nullable(); // Specific params for this chunk
            $table->integer('progress')->default(0); // 0-100
            $table->integer('credits_earned')->default(0);
            $table->string('result_hash')->nullable(); // Hash of the result for verification
            $table->string('result_file')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['render_job_id', 'status']);
            $table->index(['gpu_node_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_chunks');
    }
};
