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
        // 1. Transaction_Header: Drop equipment_id, Add detail
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
            $table->text('detail')->nullable()->after('internal_id');
        });

        // 2. Test_Methods: Add equipment_id
        Schema::table('Test_Methods', function (Blueprint $table) {
            $table->unsignedInteger('equipment_id')->nullable()->after('method_name');
            $table->foreign('equipment_id')->references('equipment_id')->on('Equipments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Test_Methods', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
        });

        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropColumn('detail');
            $table->unsignedInteger('equipment_id')->nullable();
            $table->foreign('equipment_id')->references('equipment_id')->on('Equipments');
        });
    }
};
