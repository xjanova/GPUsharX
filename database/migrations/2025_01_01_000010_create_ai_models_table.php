<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_id')->unique();
            $table->string('name');
            $table->string('huggingface_id');
            $table->enum('type', ['image', 'video', 'audio', 'text', '3d']);
            $table->enum('category', ['stable_diffusion', 'flux', 'animatediff', 'cogvideo', 'other']);
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('vram_required_mb')->default(8192);
            $table->bigInteger('size_mb')->default(0);
            $table->json('default_params')->nullable();
            $table->json('supported_params')->nullable();
            $table->enum('status', ['available', 'downloading', 'installed', 'disabled'])->default('available');
            $table->integer('download_progress')->default(0);
            $table->string('local_path')->nullable();
            $table->integer('popularity')->default(0);
            $table->decimal('avg_generation_time', 8, 2)->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('model_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['queued', 'downloading', 'completed', 'failed'])->default('queued');
            $table->integer('progress')->default(0);
            $table->string('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_downloads');
        Schema::dropIfExists('ai_models');
    }
};
