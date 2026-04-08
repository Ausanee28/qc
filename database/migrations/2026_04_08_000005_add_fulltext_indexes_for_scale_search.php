<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->fullText(['detail', 'dmc', 'line'], 'ft_th_search');
        });

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->fullText(['remark', 'max_value', 'min_value'], 'ft_td_search');
        });

        Schema::table('External_Users', function (Blueprint $table) {
            $table->fullText(['external_name'], 'ft_eu_name');
        });

        Schema::table('Internal_Users', function (Blueprint $table) {
            $table->fullText(['name'], 'ft_iu_name');
        });

        Schema::table('Test_Methods', function (Blueprint $table) {
            $table->fullText(['method_name'], 'ft_tm_name');
        });
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('Test_Methods', function (Blueprint $table) {
            $table->dropFullText('ft_tm_name');
        });

        Schema::table('Internal_Users', function (Blueprint $table) {
            $table->dropFullText('ft_iu_name');
        });

        Schema::table('External_Users', function (Blueprint $table) {
            $table->dropFullText('ft_eu_name');
        });

        Schema::table('Transaction_Detail', function (Blueprint $table) {
            $table->dropFullText('ft_td_search');
        });

        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropFullText('ft_th_search');
        });
    }
};

