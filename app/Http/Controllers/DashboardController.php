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
                'weeklyData' => $this->metricsService->getWeeklyTrend(),
                'dailyData' => $this->metricsService->getDailyTrend(),
                'monthlyData' => $this->metricsService->getMonthlyTrend(),
                'inspectorData' => $this->metricsService->getInspectorData(5, $from, $to)->toArray(),
            ];
        });

        return Inertia::render('DashboardSimple', $payload);
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
