<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->json('installed_models')->nullable()->after('gpu_specs');
        });
    }

    public function down(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->dropColumn('installed_models');
        });
    }
};
