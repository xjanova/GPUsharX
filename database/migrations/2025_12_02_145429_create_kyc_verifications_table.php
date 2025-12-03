<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Personal Information
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('nationality')->default('TH');
            $table->string('id_type'); // national_id, passport, driving_license
            $table->string('id_number');

            // Contact Information
            $table->string('phone_number');
            $table->text('address');
            $table->string('province');
            $table->string('district');
            $table->string('postal_code');

            // Document Images (stored paths)
            $table->string('id_front_image'); // Front of ID card
            $table->string('id_back_image')->nullable(); // Back of ID card
            $table->string('selfie_image'); // Selfie holding ID

            // Bank Account for Withdrawal
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('bank_account_name');

            // Verification Status
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Meta
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->integer('submission_count')->default(1);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('id_number');
        });

        // Add kyc_status to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('kyc_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('status');
            $table->decimal('withdrawal_limit', 12, 2)->default(0)->after('kyc_status'); // 0 = no limit after KYC
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'withdrawal_limit']);
        });

        Schema::dropIfExists('kyc_verifications');
    }
};
