<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use App\Services\DashboardMetricsService;
use App\Support\DashboardCache;

class DashboardController extends Controller
{
    private DashboardMetricsService $metricsService;
    private array $primaryPayloadMemo = [];
    private array $secondaryPayloadMemo = [];

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'month');

        // Compute date range based on period
        [$from, $to] = $this->getDateRange($period);

        $cacheKey = DashboardCache::summaryKey($period);

        $basePayload = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($period, $from, $to) {
            return [
                'currentPeriod' => $period,
                'metrics' => $this->metricsService->getOverviewMetrics($from, $to),
            ];
        });

        return Inertia::render('Dashboard', [
            ...$basePayload,
            'weeklyData' => Inertia::defer(fn () => $this->getPrimaryPayload($period, $from, $to)['weeklyData'], 'dashboard-primary'),
            'equipRank' => Inertia::defer(fn () => $this->getPrimaryPayload($period, $from, $to)['equipRank'], 'dashboard-primary'),
            'failByEquip' => Inertia::defer(fn () => $this->getPrimaryPayload($period, $from, $to)['failByEquip'], 'dashboard-primary'),
            'inspectorData' => Inertia::defer(fn () => $this->getPrimaryPayload($period, $from, $to)['inspectorData'], 'dashboard-primary'),
            'dailyData' => Inertia::optional(fn () => $this->getSecondaryPayload($period, $from, $to)['dailyData']),
            'monthlyData' => Inertia::optional(fn () => $this->getSecondaryPayload($period, $from, $to)['monthlyData']),
            'inspectorEff' => Inertia::optional(fn () => $this->getSecondaryPayload($period, $from, $to)['inspectorEff']),
            'recentActivities' => Inertia::optional(fn () => $this->getSecondaryPayload($period, $from, $to)['recentActivities']),
        ]);
    }

    private function getPrimaryPayload(string $period, Carbon $from, Carbon $to): array
    {
        return $this->primaryPayloadMemo[$period] ??= Cache::remember(DashboardCache::primaryKey($period), now()->addMinutes(10), function () use ($from, $to) {
            return [
                'weeklyData' => $this->metricsService->getWeeklyTrend(),
                'equipRank' => $this->metricsService->getEquipmentRanking(5, $from, $to),
                'failByEquip' => $this->metricsService->getFailuresByEquipment(5, $from, $to),
                'inspectorData' => $this->metricsService->getInspectorData(5, $from, $to),
            ];
        });
    }

    private function getSecondaryPayload(string $period, Carbon $from, Carbon $to): array
    {
        return $this->secondaryPayloadMemo[$period] ??= Cache::remember(DashboardCache::secondaryKey($period), now()->addMinutes(10), function () use ($from, $to) {
            return [
                'dailyData' => $this->metricsService->getDailyTrend(),
                'monthlyData' => $this->metricsService->getMonthlyTrend(),
                'inspectorEff' => $this->metricsService->getInspectorEfficiency(5, $from, $to),
                'recentActivities' => $this->metricsService->getRecentActivities(5, $from, $to),
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
