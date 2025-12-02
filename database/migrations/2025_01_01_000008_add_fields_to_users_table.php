<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin', 'moderator'])->default('user')->after('email');
            $table->string('referral_code')->unique()->nullable()->after('role');
            $table->foreignId('referred_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('balance', 16, 8)->default(0)->after('referred_by'); // Current available balance
            $table->decimal('total_earned', 16, 8)->default(0)->after('balance');
            $table->decimal('total_withdrawn', 16, 8)->default(0)->after('total_earned');
            $table->decimal('pending_earnings', 16, 8)->default(0)->after('total_withdrawn');
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active');
            $table->json('payment_info')->nullable(); // Saved payment methods
            $table->timestamp('last_activity')->nullable();

            $table->index('referral_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'role', 'referral_code', 'referred_by', 'balance',
                'total_earned', 'total_withdrawn', 'pending_earnings',
                'status', 'payment_info', 'last_activity'
            ]);
        });
    }
};
