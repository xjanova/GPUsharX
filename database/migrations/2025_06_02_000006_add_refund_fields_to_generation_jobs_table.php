<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_jobs', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('error_message');
            $table->decimal('refunded_amount', 10, 2)->nullable()->after('refunded_at');
        });

        // เพิ่ม retry_count ให้ job_chunks ถ้ายังไม่มี
        if (!Schema::hasColumn('job_chunks', 'retry_count')) {
            Schema::table('job_chunks', function (Blueprint $table) {
                $table->integer('retry_count')->default(0)->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('generation_jobs', function (Blueprint $table) {
            $table->dropColumn(['refunded_at', 'refunded_amount']);
        });

        if (Schema::hasColumn('job_chunks', 'retry_count')) {
            Schema::table('job_chunks', function (Blueprint $table) {
                $table->dropColumn('retry_count');
            });
        }
    }
};
