<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QcSeeder extends Seeder
{
    public function run(): void
    {
        // Departments
        DB::table('Departments')->insert([
            ['department_name' => 'Production Line A', 'internal_phone' => '1001'],
            ['department_name' => 'Production Line B', 'internal_phone' => '1002'],
            ['department_name' => 'Assembly', 'internal_phone' => '1003'],
            ['department_name' => 'Quality Assurance', 'internal_phone' => '1004'],
        ]);

        // Internal Users
        $password = Hash::make('password');
        DB::table('Internal_Users')->insert([
            [
                'user_name' => 'admin',
                'user_password' => $password,
                'employee_id' => 'EMP001',
                'name' => 'Admin User',
                'email' => 'admin@local.test',
                'email_verified_at' => now(),
                'role' => 'admin',
            ],
            [
                'user_name' => 'inspector1',
                'user_password' => $password,
                'employee_id' => 'EMP002',
                'name' => 'Somchai Tester',
                'email' => 'inspector1@local.test',
                'email_verified_at' => now(),
                'role' => 'inspector',
            ],
            [
                'user_name' => 'inspector2',
                'user_password' => $password,
                'employee_id' => 'EMP003',
                'name' => 'Nattaya Inspector',
                'email' => 'inspector2@local.test',
                'email_verified_at' => now(),
                'role' => 'inspector',
            ],
        ]);

        // External Users
        DB::table('External_Users')->insert([
            ['external_name' => 'Anong Sender', 'department_id' => 1],
            ['external_name' => 'Boonmee Operator', 'department_id' => 2],
            ['external_name' => 'Chaiya Supervisor', 'department_id' => 3],
        ]);

        // Equipments
        DB::table('Equipments')->insert([
            ['equipment_name' => 'Caliper'],
            ['equipment_name' => 'Micrometer'],
            ['equipment_name' => 'CMM Machine'],
            ['equipment_name' => 'Hardness Tester'],
            ['equipment_name' => 'Surface Roughness Tester'],
        ]);

        // Test Methods
        DB::table('Test_Methods')->insert([
            ['method_name' => 'Dimensional Check', 'tool_name' => 'Caliper'],
            ['method_name' => 'Surface Inspection', 'tool_name' => 'Visual'],
            ['method_name' => 'Hardness Test', 'tool_name' => 'Rockwell Hardness Tester'],
            ['method_name' => 'Roughness Measurement', 'tool_name' => 'Surface Roughness Tester'],
            ['method_name' => 'CMM Measurement', 'tool_name' => 'CMM Machine'],
        ]);
    }
}
