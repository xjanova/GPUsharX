<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral tree tracking
        Schema::create('referral_trees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->integer('level')->default(1); // 1 = direct, 2 = 2nd tier, etc
            $table->decimal('commission_rate', 5, 2)->default(5.00); // percentage
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'referrer_id']);
        });

        // Referral earnings history
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who earns
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade'); // who generated
            $table->foreignId('earning_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->integer('level')->default(1);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamps();
        });

        // User GPU rankings
        Schema::create('gpu_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('gpu_node_id')->constrained()->onDelete('cascade');
            $table->integer('benchmark_score')->default(0);
            $table->integer('stars')->default(1); // 1-5 stars
            $table->enum('rank', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze');
            $table->string('rank_title')->default('Beginner Miner');
            $table->json('benchmark_details')->nullable();
            $table->timestamps();
        });

        // Earnings showcase (public)
        Schema::create('earnings_showcases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('display_amount', 12, 2);
            $table->enum('period', ['daily', 'weekly', 'monthly', 'total']);
            $table->string('screenshot')->nullable();
            $table->text('message')->nullable();
            $table->integer('likes')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        // Platform settings
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, float, boolean, json
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('earnings_showcases');
        Schema::dropIfExists('gpu_rankings');
        Schema::dropIfExists('referral_earnings');
        Schema::dropIfExists('referral_trees');
    }
};
