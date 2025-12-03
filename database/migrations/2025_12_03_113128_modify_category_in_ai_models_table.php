<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change category from enum to string to support more categories
        Schema::table('ai_models', function (Blueprint $table) {
            $table->string('category', 50)->default('other')->change();
        });
    }

    public function down(): void
    {
        // Revert back to enum (might lose data for new categories)
        Schema::table('ai_models', function (Blueprint $table) {
            $table->enum('category', ['stable_diffusion', 'flux', 'animatediff', 'cogvideo', 'other'])->default('other')->change();
        });
    }
};
