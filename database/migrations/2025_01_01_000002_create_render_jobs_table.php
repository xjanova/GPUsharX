<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('render_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['image', 'video', 'animation', '3d_render']);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'queued', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('estimated_credits')->default(0); // How much this job pays
            $table->integer('actual_credits')->nullable(); // Actual credits after completion
            $table->integer('total_chunks')->default(1); // Split job into chunks
            $table->integer('completed_chunks')->default(0);
            $table->integer('required_vram_mb')->default(4096); // Minimum VRAM required
            $table->integer('estimated_time_seconds')->nullable();
            $table->json('job_params')->nullable(); // Render parameters (prompt, resolution, etc.)
            $table->string('input_file')->nullable();
            $table->string('output_file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_jobs');
    }
};
