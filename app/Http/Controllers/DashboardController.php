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
    private array $trendPayloadMemo = [];

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

        return Inertia::render('DashboardSimple', [
            ...$basePayload,
            'weeklyData' => Inertia::defer(fn () => $this->getTrendPayload($period)['weeklyData'], 'dashboard-trends'),
            'dailyData' => Inertia::defer(fn () => $this->getTrendPayload($period)['dailyData'], 'dashboard-trends'),
            'monthlyData' => Inertia::defer(fn () => $this->getTrendPayload($period)['monthlyData'], 'dashboard-trends'),
        ]);
    }

    private function getTrendPayload(string $period): array
    {
        return $this->trendPayloadMemo[$period] ??= Cache::remember(DashboardCache::primaryKey($period), now()->addMinutes(10), function () {
            return [
                'weeklyData' => $this->metricsService->getWeeklyTrend(),
                'dailyData' => $this->metricsService->getDailyTrend(),
                'monthlyData' => $this->metricsService->getMonthlyTrend(),
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
