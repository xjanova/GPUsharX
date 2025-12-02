<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallet transactions table
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->enum('type', ['deposit', 'withdrawal', 'earning', 'referral', 'bonus', 'fee', 'refund']);
            $table->decimal('amount', 16, 8);
            $table->decimal('fee', 16, 8)->default(0);
            $table->decimal('balance_before', 16, 8);
            $table->decimal('balance_after', 16, 8);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('reference_type')->nullable(); // earning, payout, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Withdrawal requests table
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('request_id')->unique();
            $table->decimal('amount', 16, 8);
            $table->decimal('fee', 16, 8)->default(0);
            $table->decimal('net_amount', 16, 8);
            $table->enum('payment_method', ['bank_transfer', 'promptpay', 'truemoney', 'paypal', 'crypto']);
            $table->json('payment_details');
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->string('transaction_ref')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Earning transfers to wallet (when pending reaches threshold)
        Schema::create('earning_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 16, 8);
            $table->decimal('pending_before', 16, 8);
            $table->decimal('pending_after', 16, 8);
            $table->decimal('balance_before', 16, 8);
            $table->decimal('balance_after', 16, 8);
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->onDelete('set null');
            $table->timestamps();
        });

        // Add min_withdrawal_amount to platform_settings if not exists
        // This will be handled by seeder
    }

    public function down(): void
    {
        Schema::dropIfExists('earning_transfers');
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('wallet_transactions');
    }
};
