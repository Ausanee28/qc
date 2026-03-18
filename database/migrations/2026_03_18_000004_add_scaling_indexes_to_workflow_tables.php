<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->index(['deleted_at', 'receive_date'], 'idx_th_deleted_receive');
        });

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->index(['deleted_at', 'transaction_id', 'judgement'], 'idx_td_deleted_tx_judgement');
            $table->index(['deleted_at', 'internal_id', 'start_time', 'end_time'], 'idx_td_deleted_internal_time');
        });
    }

    public function down(): void
    {
        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->dropIndex('idx_td_deleted_internal_time');
            $table->dropIndex('idx_td_deleted_tx_judgement');
        });

        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropIndex('idx_th_deleted_receive');
        });
    }
};

