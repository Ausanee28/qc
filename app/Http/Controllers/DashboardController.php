<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use App\Services\DashboardMetricsService;
use App\Support\DashboardCache;

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

        [$from, $to] = $this->getDateRange($period);
        $payload = DashboardCache::store()->remember(DashboardCache::pageKey($period), now()->addMinutes(10), function () use ($period, $from, $to) {
            return [
                'currentPeriod' => $period,
                'metrics' => $this->metricsService->getOverviewMetrics($from, $to),
            ];
        });

        return Inertia::render('DashboardSimple', [
            ...$payload,
            'weeklyData' => Inertia::defer(fn () => DashboardCache::store()->remember(
                DashboardCache::simpleWeeklyKey($period),
                now()->addMinutes(10),
                fn () => $this->metricsService->getWeeklyTrend()
            ), 'dashboard-secondary'),
            'fourWeekData' => Inertia::defer(fn () => DashboardCache::store()->remember(
                DashboardCache::simpleFourWeekKey($period),
                now()->addMinutes(10),
                fn () => $this->metricsService->getFourWeekTrend()
            ), 'dashboard-secondary'),
            'dailyData' => Inertia::defer(fn () => DashboardCache::store()->remember(
                DashboardCache::simpleDailyKey($period),
                now()->addMinutes(10),
                fn () => $this->metricsService->getDailyTrend()
            ), 'dashboard-secondary'),
            'monthlyData' => Inertia::defer(fn () => DashboardCache::store()->remember(
                DashboardCache::simpleMonthlyKey($period),
                now()->addMinutes(10),
                fn () => $this->metricsService->getMonthlyTrend()
            ), 'dashboard-secondary'),
            'inspectorData' => Inertia::defer(fn () => DashboardCache::store()->remember(
                DashboardCache::simpleInspectorsKey($period),
                now()->addMinutes(10),
                fn () => $this->metricsService->getInspectorData(5, $from, $to)->toArray()
            ), 'dashboard-secondary'),
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
