<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_today_metrics_follow_received_job_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-05 10:00:00'));

        try {
            $user = User::factory()->create();
            $methodId = $this->seedMethod();
            $todayJobId = $this->seedJob($user->user_id, now());
            $yesterdayJobId = $this->seedJob($user->user_id, now()->subDay());

            DB::table('Transaction_Detail')->insert([
                'transaction_id' => $todayJobId,
                'method_id' => $methodId,
                'internal_id' => $user->user_id,
                'start_time' => now()->subHour(),
                'end_time' => now(),
                'duration_sec' => 3600,
                'judgement' => 'OK',
                'deleted_at' => null,
            ]);

            DB::table('Transaction_Detail')->insert([
                'transaction_id' => $yesterdayJobId,
                'method_id' => $methodId,
                'internal_id' => $user->user_id,
                'start_time' => now()->subHour(),
                'end_time' => now(),
                'duration_sec' => 3600,
                'judgement' => 'NG',
                'deleted_at' => null,
            ]);

            $service = app(DashboardMetricsService::class);
            $metrics = $service->getOverviewMetrics(now()->startOfDay(), now()->endOfDay());
            $todayJudgements = $service->getTodayJudgements();

            $this->assertSame(1, $metrics['todayCount']);
            $this->assertSame(1, $metrics['totalTests']);
            $this->assertSame(1, $metrics['okCount']);
            $this->assertSame(0, $metrics['ngCount']);
            $this->assertSame(1, $metrics['todayOK']);
            $this->assertSame(0, $metrics['todayNG']);
            $this->assertSame(['todayOK' => 1, 'todayNG' => 0], $todayJudgements);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function seedMethod(): int
    {
        $equipmentId = DB::table('Equipments')->insertGetId([
            'equipment_name' => 'Scope',
        ]);

        return DB::table('Test_Methods')->insertGetId([
            'method_name' => 'Visual Inspection',
            'tool_name' => 'Scope',
            'equipment_id' => $equipmentId,
            'is_active' => true,
        ]);
    }

    private function seedJob(int $internalUserId, Carbon $receiveDate): int
    {
        $departmentId = DB::table('Departments')->insertGetId([
            'department_name' => 'QA',
            'internal_phone' => '1234',
        ]);

        $externalId = DB::table('External_Users')->insertGetId([
            'external_name' => 'Sender',
            'department_id' => $departmentId,
            'is_active' => true,
        ]);

        return DB::table('Transaction_Header')->insertGetId([
            'external_id' => $externalId,
            'internal_id' => $internalUserId,
            'detail' => 'Dashboard sample',
            'dmc' => 'DMC-001',
            'line' => 'L1',
            'receive_date' => $receiveDate,
            'return_date' => null,
            'deleted_at' => null,
        ]);
    }
}
