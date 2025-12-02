<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version'); // e.g., "1.0.0"
            $table->string('platform'); // windows, linux, mac
            $table->string('filename');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum_sha256')->nullable();
            $table->text('release_notes')->nullable();
            $table->string('min_gpu_memory')->default('4GB');
            $table->string('min_ram')->default('8GB');
            $table->boolean('is_latest')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();

            $table->unique(['version', 'platform']); // Same version can exist for different platforms
            $table->index(['platform', 'is_latest']);
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_versions');
    }
};
