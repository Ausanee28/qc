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
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->string('sender_department')->nullable()->after('internal_id');
            $table->string('sender_messenger')->nullable()->after('sender_department');
        });

        // Add 'อื่นๆ (Other)' to Departments if it doesn't exist
        $otherDept = DB::table('Departments')->where('department_name', 'อื่นๆ (Other)')->first();
        if (!$otherDept) {
            $deptId = DB::table('Departments')->insertGetId([
                'department_name' => 'อื่นๆ (Other)'
            ]);
        } else {
            $deptId = $otherDept->department_id;
        }

        // Add 'อื่นๆ (Other)' to External_Users if it doesn't exist
        DB::table('External_Users')->updateOrInsert(
            ['external_name' => 'อื่นๆ (Other)'],
            ['department_id' => $deptId, 'is_active' => true]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Transaction_Header', function (Blueprint $table) {
            $table->dropColumn(['sender_department', 'sender_messenger']);
        });
    }
};
