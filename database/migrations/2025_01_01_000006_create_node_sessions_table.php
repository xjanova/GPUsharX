<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track online sessions for anti-cheat and statistics
        Schema::create('node_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpu_node_id')->constrained()->onDelete('cascade');
            $table->string('session_token')->unique();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_work_seconds')->default(0);
            $table->integer('chunks_completed')->default(0);
            $table->decimal('credits_earned', 16, 8)->default(0);
            $table->string('ip_address');
            $table->json('system_info')->nullable(); // Detailed system snapshot
            $table->boolean('is_valid')->default(true); // For anti-cheat flagging
            $table->timestamps();

            $table->index(['gpu_node_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_sessions');
    }
};
