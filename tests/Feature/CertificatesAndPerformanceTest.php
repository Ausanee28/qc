<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificatesAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_certificates_and_performance_pages(): void
    {
        $user = User::factory()->create(['role' => 'inspector']);
        $this->seedMinimalWorkflowData($user->user_id);

        $this->actingAs($user)
            ->get(route('certificates.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('performance.index'))
            ->assertOk();
    }

    private function seedMinimalWorkflowData(int $internalUserId): int
    {
        $departmentId = DB::table('Departments')->insertGetId([
            'department_name' => 'QA',
            'internal_phone' => '1234',
        ]);

        $externalId = DB::table('External_Users')->insertGetId([
            'external_name' => 'External A',
            'department_id' => $departmentId,
        ]);

        $equipmentId = DB::table('Equipments')->insertGetId([
            'equipment_name' => 'EQ-A',
        ]);

        $methodId = DB::table('Test_Methods')->insertGetId([
            'method_name' => 'Method A',
            'tool_name' => 'Tool A',
            'equipment_id' => $equipmentId,
        ]);

        $jobId = DB::table('Transaction_Header')->insertGetId([
            'external_id' => $externalId,
            'internal_id' => $internalUserId,
            'detail' => 'Regression seed',
            'dmc' => 'DMC-001',
            'line' => 'L1',
            'receive_date' => now()->subDay(),
            'return_date' => now(),
            'deleted_at' => null,
        ]);

        DB::table('Transaction_Detail')->insert([
            'transaction_id' => $jobId,
            'method_id' => $methodId,
            'internal_id' => $internalUserId,
            'start_time' => now()->subDay()->subMinutes(10),
            'end_time' => now()->subDay(),
            'duration_sec' => 600,
            'judgement' => 'OK',
            'remark' => 'seed',
            'deleted_at' => null,
        ]);

        return $jobId;
    }
}
