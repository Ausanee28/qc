<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->softDeletes()->after('return_date');
            $table->index('deleted_at', 'idx_th_deleted_at');
        });

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->softDeletes()->after('remark');
            $table->index('deleted_at', 'idx_td_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->dropIndex('idx_td_deleted_at');
            $table->dropSoftDeletes();
        });

        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropIndex('idx_th_deleted_at');
            $table->dropSoftDeletes();
        });
    }
};
