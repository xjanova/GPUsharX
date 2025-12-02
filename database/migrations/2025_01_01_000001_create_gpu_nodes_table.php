<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpu_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('node_id')->unique(); // Unique identifier for the node
            $table->string('machine_id')->unique(); // Hardware fingerprint
            $table->string('gpu_model');
            $table->integer('gpu_vram_mb'); // VRAM in MB
            $table->integer('benchmark_score')->default(0); // Performance score
            $table->decimal('hashrate', 12, 4)->default(0); // Computed hashrate equivalent
            $table->enum('status', ['online', 'offline', 'working', 'idle', 'banned'])->default('offline');
            $table->string('ip_address')->nullable();
            $table->string('client_version')->nullable();
            $table->json('gpu_specs')->nullable(); // Detailed GPU specifications
            $table->timestamp('last_heartbeat')->nullable();
            $table->timestamp('last_benchmark')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gpu_nodes');
    }
};
