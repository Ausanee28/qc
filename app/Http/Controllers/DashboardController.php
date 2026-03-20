<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\Equipment;
use App\Models\User;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

use App\Services\DashboardMetricsService;

class DashboardController extends Controller
{
    private DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'month');

        // Compute date range based on period
        [$from, $to] = $this->getDateRange($period);

        $cacheKey = "dashboard.metrics.{$period}";

        $basePayload = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($period, $from, $to) {
            $counts = $this->metricsService->getCounts($from, $to);
            $todayJudgements = $this->metricsService->getTodayJudgements();
            $pendingCountQuery = DB::table('Transaction_Header')->whereNull('return_date');
            if (SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at')) {
                $pendingCountQuery->whereNull('deleted_at');
            }

            return [
                'currentPeriod' => $period,
                'metrics' => [
                    'todayCount' => $this->metricsService->getTodayCount(),
                    'monthCount' => $this->metricsService->getMonthCount(),
                    'okCount' => $counts['okCount'],
                    'ngCount' => $counts['ngCount'],
                    'pendingCount' => $pendingCountQuery->count(),
                    'todayOK' => $todayJudgements['todayOK'],
                    'todayNG' => $todayJudgements['todayNG'],
                    'yieldRate' => $counts['yieldRate'],
                    'defectRate' => $counts['defectRate'],
                    'avgTestTime' => $this->metricsService->getAverageTestTimeMinutes($from, $to),
                    'totalTests' => $counts['totalTests'],
                    'testsPerJob' => $this->metricsService->getTestsPerJob($from, $to),
                ],
            ];
        });

        return Inertia::render('Dashboard', [
            ...$basePayload,
            'weeklyData' => Inertia::defer(fn () => Cache::remember('dashboard.heavy.weekly', now()->addSeconds(60), fn () => $this->metricsService->getWeeklyTrend()), 'dashboard-heavy'),
            'dailyData' => Inertia::defer(fn () => Cache::remember('dashboard.heavy.daily', now()->addSeconds(60), fn () => $this->metricsService->getDailyTrend()), 'dashboard-heavy'),
            'monthlyData' => Inertia::defer(fn () => Cache::remember('dashboard.heavy.monthly', now()->addSeconds(60), fn () => $this->metricsService->getMonthlyTrend()), 'dashboard-heavy'),
            'equipRank' => Inertia::defer(fn () => Cache::remember("dashboard.heavy.{$period}.equip-rank", now()->addSeconds(60), fn () => $this->metricsService->getEquipmentRanking(5, $from, $to)), 'dashboard-heavy'),
            'failByEquip' => Inertia::defer(fn () => Cache::remember("dashboard.heavy.{$period}.fail-by-equip", now()->addSeconds(60), fn () => $this->metricsService->getFailuresByEquipment(5, $from, $to)), 'dashboard-heavy'),
            'inspectorEff' => Inertia::defer(fn () => Cache::remember("dashboard.heavy.{$period}.inspector-eff", now()->addSeconds(60), fn () => $this->metricsService->getInspectorEfficiency(5, $from, $to)), 'dashboard-heavy'),
            'recentActivities' => Inertia::defer(fn () => Cache::remember("dashboard.heavy.{$period}.recent-activities", now()->addSeconds(30), fn () => $this->metricsService->getRecentActivities(5, $from, $to)), 'dashboard-heavy'),
            'inspectorData' => Inertia::defer(fn () => Cache::remember("dashboard.heavy.{$period}.inspector-data", now()->addSeconds(60), fn () => $this->metricsService->getInspectorData(5, $from, $to)), 'dashboard-heavy'),
        ]);
    }

    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::today(), Carbon::today()->endOfDay()],
            'week' => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
            '30days' => [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()],
            'quarter' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfDay()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
        };
    }
}
