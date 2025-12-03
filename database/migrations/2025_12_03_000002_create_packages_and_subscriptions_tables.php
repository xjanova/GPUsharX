<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Packages table - แพ็กเกจที่เสนอขาย
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['credits', 'subscription'])->default('credits');
            $table->enum('billing_period', ['one_time', 'monthly', 'yearly'])->default('one_time');

            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable(); // สำหรับแสดงส่วนลด
            $table->string('currency', 3)->default('USD');

            // Credits
            $table->integer('credits_amount')->default(0);
            $table->integer('bonus_credits')->default(0);

            // Subscription features
            $table->integer('monthly_credits')->default(0); // Credits ที่ได้ต่อเดือน
            $table->integer('priority_level')->default(0); // ความสำคัญในคิว (0-100)
            $table->boolean('unlimited_generations')->default(false);
            $table->json('features')->nullable(); // Feature list

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        // User Subscriptions
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');

            $table->enum('status', ['active', 'cancelled', 'expired', 'past_due'])->default('active');

            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();

            // Payment
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->decimal('amount_paid', 10, 2);

            // Auto-renewal
            $table->boolean('auto_renew')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        // Credit Purchases (one-time)
        Schema::create('credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');

            $table->integer('credits_purchased');
            $table->integer('bonus_credits')->default(0);
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3)->default('USD');

            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_purchases');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('packages');
    }
};
