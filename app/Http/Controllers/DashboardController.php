<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $basePayload = Cache::remember($cacheKey, now()->addMinutes(3), function () use ($period, $from, $to) {
            return [
                'currentPeriod' => $period,
                'metrics' => $this->metricsService->getOverviewMetrics($from, $to),
            ];
        });

        return Inertia::render('Dashboard', [
            ...$basePayload,
            'weeklyData' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['weeklyData'], 'dashboard-heavy'),
            'dailyData' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['dailyData'], 'dashboard-heavy'),
            'monthlyData' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['monthlyData'], 'dashboard-heavy'),
            'equipRank' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['equipRank'], 'dashboard-heavy'),
            'failByEquip' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['failByEquip'], 'dashboard-heavy'),
            'inspectorEff' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['inspectorEff'], 'dashboard-heavy'),
            'recentActivities' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['recentActivities'], 'dashboard-heavy'),
            'inspectorData' => Inertia::defer(fn () => $this->getHeavyPayload($period, $from, $to)['inspectorData'], 'dashboard-heavy'),
        ]);
    }

    private function getHeavyPayload(string $period, Carbon $from, Carbon $to): array
    {
        return Cache::remember("dashboard.heavy.{$period}", now()->addMinutes(3), function () use ($from, $to) {
            return [
                'weeklyData' => $this->metricsService->getWeeklyTrend(),
                'dailyData' => $this->metricsService->getDailyTrend(),
                'monthlyData' => $this->metricsService->getMonthlyTrend(),
                'equipRank' => $this->metricsService->getEquipmentRanking(5, $from, $to),
                'failByEquip' => $this->metricsService->getFailuresByEquipment(5, $from, $to),
                'inspectorEff' => $this->metricsService->getInspectorEfficiency(5, $from, $to),
                'recentActivities' => $this->metricsService->getRecentActivities(5, $from, $to),
                'inspectorData' => $this->metricsService->getInspectorData(5, $from, $to),
            ];
        });
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
