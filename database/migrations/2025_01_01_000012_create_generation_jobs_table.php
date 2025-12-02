<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User generation requests
        Schema::create('generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_model_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video', 'audio', '3d']);
            $table->text('prompt');
            $table->text('negative_prompt')->nullable();
            $table->json('params')->nullable(); // width, height, steps, cfg, etc
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->integer('progress')->default(0);
            $table->string('result_url')->nullable();
            $table->string('result_thumbnail')->nullable();
            $table->json('result_metadata')->nullable();
            $table->decimal('credits_used', 10, 2)->default(0);
            $table->integer('processing_time_ms')->nullable();
            $table->foreignId('processed_by_node')->nullable()->constrained('gpu_nodes')->onDelete('set null');
            $table->text('error_message')->nullable();
            $table->enum('visibility', ['private', 'public', 'unlisted'])->default('private');
            $table->integer('likes')->default(0);
            $table->timestamps();
        });

        // Generation gallery (public)
        Schema::create('generation_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_job_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->integer('downloads')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_nsfw')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_gallery');
        Schema::dropIfExists('generation_jobs');
    }
};
