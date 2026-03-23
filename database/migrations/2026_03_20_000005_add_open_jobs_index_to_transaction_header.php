<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDeletedAt = Schema::hasColumn('Transaction_Header', 'deleted_at');

        Schema::table('Transaction_Header', function (Blueprint $table) use ($hasDeletedAt) {
            if ($hasDeletedAt) {
                $table->index(['return_date', 'deleted_at', 'receive_date'], 'idx_th_return_deleted_receive');
                return;
            }

            $table->index(['return_date', 'receive_date'], 'idx_th_return_receive');
        });
    }

    public function down(): void
    {
        $hasDeletedAt = Schema::hasColumn('Transaction_Header', 'deleted_at');

        Schema::table('Transaction_Header', function (Blueprint $table) use ($hasDeletedAt) {
            if ($hasDeletedAt) {
                $table->dropIndex('idx_th_return_deleted_receive');
                return;
            }

            $table->dropIndex('idx_th_return_receive');
        });
    }
};
