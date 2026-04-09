<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_performance_page_falls_back_to_live_duration_average_when_aggregate_is_stale(): void
    {
        $user = User::factory()->create(['role' => 'inspector']);
        $jobId = $this->seedMinimalWorkflowData($user->user_id);

        $methodId = DB::table('Transaction_Detail')
            ->where('transaction_id', $jobId)
            ->value('method_id');

        DB::table('Transaction_Detail')->insert([
            'transaction_id' => $jobId,
            'method_id' => $methodId,
            'internal_id' => $user->user_id,
            'start_time' => now()->subMinutes(20),
            'end_time' => now()->subMinutes(5),
            'duration_sec' => 900,
            'judgement' => 'OK',
            'remark' => 'latest sample',
            'deleted_at' => null,
        ]);

        DB::table('performance_daily_inspector_aggregates')->insert([
            'date_key' => now()->subDays(2)->toDateString(),
            'month_key' => now()->subDays(2)->format('Y-m'),
            'internal_id' => $user->user_id,
            'total_tests' => 1,
            'ok_count' => 1,
            'ng_count' => 0,
            'duration_total_sec' => 0,
            'duration_samples' => 1,
            'min_duration_sec' => 0,
            'max_duration_sec' => 0,
            'aggregated_at' => now()->subDays(2),
        ]);

        Cache::forget('performance.inspectors.30d');
        Cache::forget('performance.details.30d.recent50');

        $response = $this->actingAs($user)->get(route('performance.index'));

        $response->assertOk();

        $page = $response->viewData('page');
        $inspectors = collect(data_get($page, 'props.inspectors', []));
        $inspector = $inspectors->firstWhere('id', $user->user_id);

        $this->assertNotNull($inspector);
        $this->assertSame(750, (int) data_get($inspector, 'avg_sec', -1));
        $this->assertSame(2, (int) data_get($inspector, 'total_tests', 0));
    }

    public function test_performance_page_computes_fastest_from_timestamps_when_duration_sec_is_missing(): void
    {
        $user = User::factory()->create(['role' => 'inspector']);
        $jobId = $this->seedMinimalWorkflowData($user->user_id);

        $methodId = DB::table('Transaction_Detail')
            ->where('transaction_id', $jobId)
            ->value('method_id');

        $start = now()->subMinutes(12)->startOfMinute();
        $end = $start->copy()->addMinutes(10);

        DB::table('Transaction_Detail')->insert([
            'transaction_id' => $jobId,
            'method_id' => $methodId,
            'internal_id' => $user->user_id,
            'start_time' => $start,
            'end_time' => $end,
            'duration_sec' => null,
            'judgement' => 'OK',
            'remark' => 'duration fallback',
            'deleted_at' => null,
        ]);

        DB::table('performance_daily_inspector_aggregates')->insert([
            'date_key' => now()->toDateString(),
            'month_key' => now()->format('Y-m'),
            'internal_id' => $user->user_id,
            'total_tests' => 2,
            'ok_count' => 2,
            'ng_count' => 0,
            'duration_total_sec' => 0,
            'duration_samples' => 2,
            'min_duration_sec' => 0,
            'max_duration_sec' => 0,
            'aggregated_at' => now(),
        ]);

        Cache::forget('performance.inspectors.30d');
        Cache::forget('performance.details.30d.recent50');

        $response = $this->actingAs($user)->get(route('performance.index'));

        $response->assertOk();

        $page = $response->viewData('page');
        $inspectors = collect(data_get($page, 'props.inspectors', []));
        $inspector = $inspectors->firstWhere('id', $user->user_id);

        $this->assertNotNull($inspector);
        $this->assertSame(600, (int) data_get($inspector, 'min_sec', -1));
        $this->assertSame(600, (int) data_get($inspector, 'avg_sec', -1));
    }

    public function test_performance_page_ignores_stale_aggregate_when_live_rows_are_empty(): void
    {
        $user = User::factory()->create(['role' => 'inspector']);

        DB::table('performance_daily_inspector_aggregates')->insert([
            'date_key' => now()->subDay()->toDateString(),
            'month_key' => now()->subDay()->format('Y-m'),
            'internal_id' => $user->user_id,
            'total_tests' => 3,
            'ok_count' => 2,
            'ng_count' => 1,
            'duration_total_sec' => 0,
            'duration_samples' => 3,
            'min_duration_sec' => 0,
            'max_duration_sec' => 0,
            'aggregated_at' => now()->subDay(),
        ]);

        Cache::forget('performance.inspectors.30d');
        Cache::forget('performance.details.30d.recent50');

        $response = $this->actingAs($user)->get(route('performance.index'));

        $response->assertOk();

        $page = $response->viewData('page');
        $inspectors = collect(data_get($page, 'props.inspectors', []));

        $this->assertCount(0, $inspectors);
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
