<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\Equipment;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $todayCount = TransactionHeader::whereDate('receive_date', today())->count();
        $monthCount = TransactionHeader::whereMonth('receive_date', now()->month)
            ->whereYear('receive_date', now()->year)->count();
        $okCount = TransactionDetail::where('judgement', 'OK')->count();
        $ngCount = TransactionDetail::where('judgement', 'NG')->count();
        $pendingCount = TransactionHeader::whereNull('return_date')->count();

        $todayOK = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', today()))
            ->where('judgement', 'OK')->count();
        $todayNG = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', today()))
            ->where('judgement', 'NG')->count();

        // Weekly trend
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $d))
                ->where('judgement', 'OK')->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $d))
                ->where('judgement', 'NG')->count();
            $weeklyData[] = ['label' => now()->subDays($i)->format('D'), 'ok' => $ok, 'ng' => $ng];
        }

        // Monthly trend
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereMonth('receive_date', $m->month)->whereYear('receive_date', $m->year))
                ->where('judgement', 'OK')->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereMonth('receive_date', $m->month)->whereYear('receive_date', $m->year))
                ->where('judgement', 'NG')->count();
            $monthlyData[] = ['label' => $m->format('M'), 'ok' => $ok, 'ng' => $ng];
        }

        // Equipment ranking
        $equipRank = TransactionHeader::select('equipment_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('equipment_id')
            ->orderByDesc('cnt')
            ->limit(5)
            ->with('equipment')
            ->get()
            ->map(fn($r) => ['name' => $r->equipment->equipment_name ?? 'N/A', 'count' => $r->cnt]);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'todayCount' => $todayCount,
                'monthCount' => $monthCount,
                'okCount' => $okCount,
                'ngCount' => $ngCount,
                'pendingCount' => $pendingCount,
                'todayOK' => $todayOK,
                'todayNG' => $todayNG,
            ],
            'weeklyData' => $weeklyData,
            'monthlyData' => $monthlyData,
            'equipRank' => $equipRank,
        ]);
    }
}
