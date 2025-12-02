<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (Schema::hasColumn('ai_models', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
