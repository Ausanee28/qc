<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->index(['receive_date', 'return_date'], 'idx_dates');
        });

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->index('judgement', 'idx_judgement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropIndex('idx_dates');
        });

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->dropIndex('idx_judgement');
        });
    }
};
