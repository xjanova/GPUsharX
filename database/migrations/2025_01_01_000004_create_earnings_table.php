<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('gpu_node_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('job_chunk_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['job_reward', 'bonus', 'referral', 'adjustment', 'penalty']);
            $table->decimal('amount', 16, 8); // Can be negative for penalties
            $table->decimal('platform_fee', 16, 8)->default(0); // Platform cut
            $table->decimal('net_amount', 16, 8); // Amount after fees
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'paid'])->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earnings');
    }
};
