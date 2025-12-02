<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่ม fields สำหรับ Parallel Processing
     */
    public function up(): void
    {
        // Update render_jobs table
        Schema::table('render_jobs', function (Blueprint $table) {
            // Chunking strategy
            $table->enum('chunking_strategy', [
                'tile_based',      // แบ่งตาม image tiles (parallel)
                'step_based',      // แบ่งตาม denoising steps (sequential)
                'batch_based',     // แบ่งตาม batch items (parallel)
                'hybrid'           // ผสมผสาน
            ])->default('tile_based')->after('status');

            // Parallel configuration
            $table->json('parallel_config')->nullable()->after('chunking_strategy');
            // Example: {
            //   "width": 1024, "height": 1024,
            //   "tile_size": 512, "overlap": 64,
            //   "total_steps": 50, "blend_mode": "linear"
            // }

            // Assembly status
            $table->enum('assembly_status', [
                'pending',         // รอ chunks เสร็จ
                'ready',           // chunks เสร็จหมด พร้อม assemble
                'assembling',      // กำลังประกอบ
                'completed',       // ประกอบเสร็จ
                'failed'           // ประกอบล้มเหลว
            ])->default('pending')->after('parallel_config');

            // Final result
            $table->string('final_result_url', 500)->nullable()->after('assembly_status');
            $table->timestamp('assembly_started_at')->nullable();
            $table->timestamp('assembly_completed_at')->nullable();

            // Total nodes working on this job
            $table->integer('active_nodes_count')->default(0);
            $table->decimal('total_hashrate_assigned', 12, 4)->default(0);
        });

        // Update job_chunks table
        Schema::table('job_chunks', function (Blueprint $table) {
            // Chunk type
            $table->enum('chunk_type', [
                'tile',            // Image tile region
                'step_range',      // Denoising step range
                'batch_item',      // Batch item
                'latent_pass'      // Intermediate latent
            ])->default('tile')->after('chunk_index');

            // Chunk configuration
            $table->json('chunk_config')->nullable()->after('chunk_type');
            // For tile: {"x": 0, "y": 0, "width": 512, "height": 512, "overlap": 64}
            // For step_range: {"start_step": 1, "end_step": 25, "total_steps": 100}
            // For batch_item: {"batch_index": 0, "seed": 12345}

            // Dependencies (สำหรับ step-based)
            $table->string('depends_on_chunk_id', 64)->nullable()->after('chunk_config');
            $table->enum('dependency_status', [
                'none',            // ไม่มี dependency
                'waiting',         // รอ chunk ก่อนหน้า
                'ready',           // พร้อมทำงาน
                'blocked'          // ถูก block
            ])->default('none');

            // Partial result storage
            $table->string('partial_result_url', 500)->nullable();
            $table->string('partial_result_hash', 64)->nullable();

            // Workload weight (based on hashrate)
            $table->decimal('workload_weight', 8, 4)->default(1.0);
            // weight = node_hashrate / total_job_hashrate

            // Index for faster queries
            $table->index(['render_job_id', 'dependency_status']);
            $table->index('depends_on_chunk_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('render_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'chunking_strategy',
                'parallel_config',
                'assembly_status',
                'final_result_url',
                'assembly_started_at',
                'assembly_completed_at',
                'active_nodes_count',
                'total_hashrate_assigned',
            ]);
        });

        Schema::table('job_chunks', function (Blueprint $table) {
            $table->dropIndex(['render_job_id', 'dependency_status']);
            $table->dropIndex(['depends_on_chunk_id']);
            $table->dropColumn([
                'chunk_type',
                'chunk_config',
                'depends_on_chunk_id',
                'dependency_status',
                'partial_result_url',
                'partial_result_hash',
                'workload_weight',
            ]);
        });
    }
};
