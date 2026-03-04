<?php

namespace App\Services;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    public function getTodayCount(): int
    {
        return Cache::remember('dashboard_today_count_' . today()->format('Ymd'), 300, function () {
            return TransactionHeader::whereDate('receive_date', today())->count();
        });
    }

    public function getMonthCount(): int
    {
        return Cache::remember('dashboard_month_count_' . now()->format('Ym'), 300, function () {
            return TransactionHeader::whereMonth('receive_date', now()->month)
                ->whereYear('receive_date', now()->year)->count();
        });
    }

    public function getGlobalCounts(): array
    {
        return Cache::remember('dashboard_global_counts', 300, function () {
            $okCount = TransactionDetail::where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
            $ngCount = TransactionDetail::where('judgement', TransactionDetail::JUDGEMENT_NG)->count();
            $pendingCount = TransactionHeader::whereNull('return_date')->count();

            $totalTests = $okCount + $ngCount;
            $yieldRate = $totalTests > 0 ? round($okCount / $totalTests * 100, 1) : 0;
            $defectRate = $totalTests > 0 ? round($ngCount / $totalTests * 100, 1) : 0;

            return compact('okCount', 'ngCount', 'pendingCount', 'totalTests', 'yieldRate', 'defectRate');
        });
    }

    public function getTodayJudgements(): array
    {
        return Cache::remember('dashboard_today_judgements_' . today()->format('Ymd'), 300, function () {
            $todayOK = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', today()))
                ->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
            $todayNG = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', today()))
                ->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();

            return compact('todayOK', 'todayNG');
        });
    }

    public function getAverageTestTimeMinutes(): float
    {
        $avgTime = TransactionDetail::whereNotNull('end_time')
            ->whereNotNull('start_time')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_min')
            ->value('avg_min');

        return $avgTime ? round($avgTime) : 0;
    }

    public function getWeeklyTrend(): array
    {
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $d))
                ->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $d))
                ->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();
            $weeklyData[] = [
                'label' => now()->subDays($i)->format('D d/m'),
                'ok' => $ok,
                'ng' => $ng,
            ];
        }
        return $weeklyData;
    }

    public function getMonthlyTrend(): array
    {
        $monthlyRaw = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereMonth('receive_date', $m->month)->whereYear('receive_date', $m->year))
                ->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereMonth('receive_date', $m->month)->whereYear('receive_date', $m->year))
                ->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();

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

        $monthlyData = [];
        for ($i = 0; $i < count($monthlyRaw); $i++) {
            $mom = null;
            if ($i > 0 && $monthlyRaw[$i - 1]['yield'] > 0) {
                $mom = round($monthlyRaw[$i]['yield'] - $monthlyRaw[$i - 1]['yield'], 1);
            }
            $monthlyData[] = array_merge($monthlyRaw[$i], ['mom' => $mom]);
        }

        return $monthlyData;
    }

    public function getEquipmentRanking(int $limit = 5): \Illuminate\Support\Collection
    {
        return TransactionHeader::select('equipment_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('equipment_id')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->with('equipment')
            ->get()
            ->map(fn($r) => ['name' => $r->equipment->equipment_name ?? 'N/A', 'count' => $r->cnt]);
    }

    public function getRecentActivities(int $limit = 5): \Illuminate\Support\Collection
    {
        return TransactionHeader::with(['equipment', 'details'])
            ->whereNotNull('return_date')
            ->orderByDesc('return_date')
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
            $ok = $tx->details->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
            $ng = $tx->details->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();
            return [
                'id' => $tx->transaction_id,
                'equipment' => $tx->equipment->equipment_name ?? 'N/A',
                'dmcCode' => $tx->dmc ?? '-',
                'result' => $ng > 0 ?TransactionDetail::JUDGEMENT_NG : TransactionDetail::JUDGEMENT_OK,
                'date' => $tx->return_date ? $tx->return_date->format('d M Y') : '-',
                'ok' => $ok,
                'ng' => $ng,
            ];
        });
    }

    public function getInspectorData(int $limit = 5): \Illuminate\Support\Collection
    {
        return TransactionDetail::join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id')
            ->select(
            'Internal_Users.name',
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN judgement = '" . TransactionDetail::JUDGEMENT_OK . "' THEN 1 ELSE 0 END) as ok"),
            DB::raw("SUM(CASE WHEN judgement = '" . TransactionDetail::JUDGEMENT_NG . "' THEN 1 ELSE 0 END) as ng")
        )
            ->whereNotNull('internal_id')
            ->groupBy('internal_id', 'Internal_Users.name')
            ->orderByDesc('total')
            ->limit($limit)
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
    }

    public function getFailuresByEquipment(int $limit = 5): \Illuminate\Support\Collection
    {
        return TransactionDetail::join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
            ->join('Equipments', 'Transaction_Header.equipment_id', '=', 'Equipments.equipment_id')
            ->where('Transaction_Detail.judgement', TransactionDetail::JUDGEMENT_NG)
            ->select('Equipments.equipment_name as name', DB::raw('COUNT(*) as count'))
            ->groupBy('Equipments.equipment_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function getInspectorEfficiency(int $limit = 5): \Illuminate\Support\Collection
    {
        return TransactionDetail::join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id')
            ->select('Internal_Users.name', DB::raw('AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_seconds'))
            ->whereNotNull('internal_id')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->whereRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) > 0')
            ->whereRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) < 3600')
            ->groupBy('internal_id', 'Internal_Users.name')
            ->orderByDesc('avg_seconds')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
            return [
                'name' => $row->name,
                'avgMinutes' => round($row->avg_seconds / 60, 2)
            ];
        });
    }
}
