<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use App\Services\DashboardMetricsService;

class DashboardController extends Controller
{
    private DashboardMetricsService $metricsService;

    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function index()
    {
        $globalCounts = $this->metricsService->getGlobalCounts();
        $todayJudgements = $this->metricsService->getTodayJudgements();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'todayCount' => $this->metricsService->getTodayCount(),
                'monthCount' => $this->metricsService->getMonthCount(),
                'okCount' => $globalCounts['okCount'],
                'ngCount' => $globalCounts['ngCount'],
                'pendingCount' => $globalCounts['pendingCount'],
                'todayOK' => $todayJudgements['todayOK'],
                'todayNG' => $todayJudgements['todayNG'],
                'yieldRate' => $globalCounts['yieldRate'],
                'defectRate' => $globalCounts['defectRate'],
                'avgTestTime' => $this->metricsService->getAverageTestTimeMinutes(),
                'totalTests' => $globalCounts['totalTests'],
            ],
            'weeklyData' => $this->metricsService->getWeeklyTrend(),
            'monthlyData' => $this->metricsService->getMonthlyTrend(),
            'equipRank' => $this->metricsService->getEquipmentRanking(),
            'failByEquip' => $this->metricsService->getFailuresByEquipment(),
            'inspectorEff' => $this->metricsService->getInspectorEfficiency(),
            'recentActivities' => $this->metricsService->getRecentActivities(),
            'inspectorData' => $this->metricsService->getInspectorData(),
        ]);
    }
}
