<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('Transaction_Detail', 'deleted_at')) {
            Schema::table('Transaction_Detail', function (Blueprint $table) {
                $table->index(['deleted_at', 'end_time', 'detail_id'], 'idx_td_deleted_end_detail');
                $table->index(['deleted_at', 'start_time', 'internal_id'], 'idx_td_deleted_start_internal');
            });
            return;
        }

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->index(['end_time', 'detail_id'], 'idx_td_end_detail');
            $table->index(['start_time', 'internal_id'], 'idx_td_start_internal');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('Transaction_Detail', 'deleted_at')) {
            Schema::table('Transaction_Detail', function (Blueprint $table) {
                $table->dropIndex('idx_td_deleted_end_detail');
                $table->dropIndex('idx_td_deleted_start_internal');
            });
            return;
        }

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->dropIndex('idx_td_end_detail');
            $table->dropIndex('idx_td_start_internal');
        });
    }
};

