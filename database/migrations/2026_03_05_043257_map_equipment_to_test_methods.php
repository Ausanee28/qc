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
        // 1. Dimensional Check -> Caliper
        DB::table('Test_Methods')
            ->where('method_name', 'Dimensional Check')
            ->update(['equipment_id' => DB::table('Equipments')->where('equipment_name', 'Caliper')->value('equipment_id')]);

        // 2. Surface Inspection -> No specific equipment out of the box (Visual), but we can leave it null or map if needed.
        
        // 3. Hardness Test -> Hardness Tester
        DB::table('Test_Methods')
            ->where('method_name', 'Hardness Test')
            ->update(['equipment_id' => DB::table('Equipments')->where('equipment_name', 'Hardness Tester')->value('equipment_id')]);

        // 4. Roughness Measurement -> Surface Roughness Tester
        DB::table('Test_Methods')
            ->where('method_name', 'Like', '%Roughness%')
            ->update(['equipment_id' => DB::table('Equipments')->where('equipment_name', 'Surface Roughness Tester')->value('equipment_id')]);

        // 5. CMM Measurement -> CMM Machine
        DB::table('Test_Methods')
            ->where('method_name', 'Like', '%CMM%')
            ->update(['equipment_id' => DB::table('Equipments')->where('equipment_name', 'CMM Machine')->value('equipment_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('Test_Methods')->update(['equipment_id' => null]);
    }
};
