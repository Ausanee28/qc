<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\Equipment;
use App\Models\User;
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

        // Yield & defect rates
        $totalTests = $okCount + $ngCount;
        $yieldRate = $totalTests > 0 ? round($okCount / $totalTests * 100, 1) : 0;
        $defectRate = $totalTests > 0 ? round($ngCount / $totalTests * 100, 1) : 0;

        // Average test time (minutes)
        $avgTime = TransactionDetail::whereNotNull('end_time')
            ->whereNotNull('start_time')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_min')
            ->value('avg_min');
        $avgTestTime = $avgTime ? round($avgTime) : 0;

        // Weekly trend
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $d))
                ->where('judgement', 'OK')->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $d))
                ->where('judgement', 'NG')->count();
            $weeklyData[] = [
                'label' => now()->subDays($i)->format('D d/m'),
                'ok' => $ok,
                'ng' => $ng,
            ];
        }

        // Monthly trend (enriched with yield, ngPercent, mom)
        $monthlyRaw = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereMonth('receive_date', $m->month)->whereYear('receive_date', $m->year))
                ->where('judgement', 'OK')->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereMonth('receive_date', $m->month)->whereYear('receive_date', $m->year))
                ->where('judgement', 'NG')->count();
            $total = $ok + $ng;
            $yield = $total > 0 ? round($ok / $total * 100, 1) : 0;
            $ngPct = $total > 0 ? round($ng / $total * 100, 1) : 0;
            $monthlyRaw[] = [
                'label' => $m->format('M'),
                'fullLabel' => $m->format('F'),
                'ok' => $ok,
                'ng' => $ng,
                'total' => $total,
                'yield' => $yield,
                'ngPercent' => $ngPct,
            ];
        }

        // Calculate MoM (month-over-month yield change)
        $monthlyData = [];
        for ($i = 0; $i < count($monthlyRaw); $i++) {
            $mom = null;
            if ($i > 0 && $monthlyRaw[$i - 1]['yield'] > 0) {
                $mom = round($monthlyRaw[$i]['yield'] - $monthlyRaw[$i - 1]['yield'], 1);
            }
            $monthlyData[] = array_merge($monthlyRaw[$i], ['mom' => $mom]);
        }

        // Equipment ranking
        $equipRank = TransactionHeader::select('equipment_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('equipment_id')
            ->orderByDesc('cnt')
            ->limit(5)
            ->with('equipment')
            ->get()
            ->map(fn($r) => ['name' => $r->equipment->equipment_name ?? 'N/A', 'count' => $r->cnt]);

        // Recent activities (last 5 completed)
        $recentActivities = TransactionHeader::with(['equipment', 'details'])
            ->whereNotNull('return_date')
            ->orderByDesc('return_date')
            ->limit(5)
            ->get()
            ->map(function ($tx) {
            $ok = $tx->details->where('judgement', 'OK')->count();
            $ng = $tx->details->where('judgement', 'NG')->count();
            return [
            'id' => $tx->transaction_id,
            'equipment' => $tx->equipment->equipment_name ?? 'N/A',
            'dmcCode' => $tx->dmc ?? '-',
            'result' => $ng > 0 ? 'NG' : 'OK',
            'date' => $tx->return_date ? $tx->return_date->format('d M Y') : '-',
            'ok' => $ok,
            'ng' => $ng,
            ];
        });

        // Inspector data (top 5 by test count) — Optimized N+1 queries
        $inspectorData = TransactionDetail::join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id')
            ->select(
            'Internal_Users.name',
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN judgement = 'OK' THEN 1 ELSE 0 END) as ok"),
            DB::raw("SUM(CASE WHEN judgement = 'NG' THEN 1 ELSE 0 END) as ng")
        )
            ->whereNotNull('internal_id')
            ->groupBy('internal_id', 'Internal_Users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
            return [
            'name' => $row->name,
            'total' => $row->total,
            'ok' => $row->ok,
            'ng' => $row->ng,
            'yield' => $row->total > 0 ? round($row->ok / $row->total * 100, 1) : 0,
            ];
        });

        // Failure by Equipment (NG counts)
        $failByEquip = TransactionDetail::join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
            ->join('Equipments', 'Transaction_Header.equipment_id', '=', 'Equipments.equipment_id')
            ->where('Transaction_Detail.judgement', 'NG')
            ->select('Equipments.equipment_name as name', DB::raw('COUNT(*) as count'))
            ->groupBy('Equipments.equipment_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Inspector Efficiency (Average test time per inspector)
        $inspectorEff = TransactionDetail::join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id')
            ->select('Internal_Users.name', DB::raw('AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_seconds'))
            ->whereNotNull('internal_id')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) > 0')
            ->whereRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) < 3600') // ignore extreme outliers
            ->groupBy('internal_id', 'Internal_Users.name')
            ->orderByDesc('avg_seconds')
            ->limit(5)
            ->get()
            ->map(function ($row) {
            return [
            'name' => $row->name,
            'avgMinutes' => round($row->avg_seconds / 60, 2)
            ];
        });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'todayCount' => $todayCount,
                'monthCount' => $monthCount,
                'okCount' => $okCount,
                'ngCount' => $ngCount,
                'pendingCount' => $pendingCount,
                'todayOK' => $todayOK,
                'todayNG' => $todayNG,
                'yieldRate' => $yieldRate,
                'defectRate' => $defectRate,
                'avgTestTime' => $avgTestTime,
                'totalTests' => $totalTests,
            ],
            'weeklyData' => $weeklyData,
            'monthlyData' => $monthlyData,
            'equipRank' => $equipRank,
            'failByEquip' => $failByEquip,
            'inspectorEff' => $inspectorEff,
            'recentActivities' => $recentActivities,
            'inspectorData' => $inspectorData,
        ]);
    }
}
