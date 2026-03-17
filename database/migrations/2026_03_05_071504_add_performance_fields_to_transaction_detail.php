<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->integer('duration_sec')->nullable()->after('end_time');
            $table->index(['internal_id', 'start_time', 'end_time'], 'idx_perf_detail');
        });

        // Backfill duration for existing rows in a driver-compatible way.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                UPDATE Transaction_Detail
                SET duration_sec = CAST((julianday(end_time) - julianday(start_time)) * 86400 AS INTEGER)
                WHERE start_time IS NOT NULL
                  AND end_time IS NOT NULL
            ");
        } else {
            DB::statement("
                UPDATE Transaction_Detail
                SET duration_sec = TIMESTAMPDIFF(SECOND, start_time, end_time)
                WHERE start_time IS NOT NULL
                  AND end_time IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->dropIndex('idx_perf_detail');
            $table->dropColumn('duration_sec');
        });
    }
};
