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
        Schema::table('generation_jobs', function (Blueprint $table) {
            $table->boolean('is_admin_test')->default(false)->after('status');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_admin_test');

            $table->index('is_admin_test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generation_jobs', function (Blueprint $table) {
            $table->dropIndex(['is_admin_test']);
            $table->dropColumn(['is_admin_test', 'created_by']);
        });
    }
};
