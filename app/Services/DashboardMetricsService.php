<?php

namespace App\Services;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardMetricsService
{
    private function durationSecondsExpression(string $startColumn, string $endColumn): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "CAST((julianday({$endColumn}) - julianday({$startColumn})) * 86400 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(SECOND, {$startColumn}, {$endColumn})";
    }

    public function getTodayCount(): int
    {
        return TransactionHeader::whereDate('receive_date', today())->count();
    }

    public function getMonthCount(): int
    {
        return TransactionHeader::whereMonth('receive_date', now()->month)
            ->whereYear('receive_date', now()->year)->count();
    }

    public function getCounts(Carbon $from, Carbon $to): array
    {
        $headerIds = TransactionHeader::whereBetween('receive_date', [$from, $to])
            ->pluck('transaction_id');

        $okCount = TransactionDetail::whereIn('transaction_id', $headerIds)
            ->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
        $ngCount = TransactionDetail::whereIn('transaction_id', $headerIds)
            ->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();

        $totalTests = $okCount + $ngCount;
        $yieldRate = $totalTests > 0 ? round($okCount / $totalTests * 100, 1) : 0;
        $defectRate = $totalTests > 0 ? round($ngCount / $totalTests * 100, 1) : 0;

        return compact('okCount', 'ngCount', 'totalTests', 'yieldRate', 'defectRate');
    }

    public function getTodayJudgements(): array
    {
        $todayOK = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', today()))
            ->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
        $todayNG = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', today()))
            ->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();

        return compact('todayOK', 'todayNG');
    }

    public function getAverageTestTimeMinutes(Carbon $from, Carbon $to): float
    {
        $avgTime = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereBetween('receive_date', [$from, $to]))
            ->whereNotNull('end_time')
            ->whereNotNull('start_time')
            ->selectRaw('AVG(duration_sec) / 60 as avg_min')
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

    public function getDailyTrend(): array
    {
        $startOfMonth = now()->startOfMonth();
        $today = now();
        $days = [];

        for ($d = $startOfMonth->copy(); $d->lte($today); $d->addDay()) {
            $date = $d->format('Y-m-d');
            $ok = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $date))
                ->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
            $ng = TransactionDetail::whereHas('transactionHeader', fn($q) => $q->whereDate('receive_date', $date))
                ->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();
            $days[] = [
                'label' => $d->format('d M'),
                'ok' => $ok,
                'ng' => $ng,
            ];
        }
        return $days;
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

    public function getEquipmentRanking(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $query = DB::table('Transaction_Detail')
            ->join('Test_Methods', 'Transaction_Detail.method_id', '=', 'Test_Methods.method_id')
            ->leftJoin('Equipments', 'Test_Methods.equipment_id', '=', 'Equipments.equipment_id');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
        }

        return $query->select(
                DB::raw("COALESCE(Equipments.equipment_name, Test_Methods.method_name) as equipment"),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('equipment')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get()
            ->map(fn($r) => ['name' => $r->equipment, 'count' => $r->cnt]);
    }

    public function getRecentActivities(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $query = TransactionHeader::with(['details'])
            ->whereNotNull('return_date');

        if ($from && $to) {
            $query->whereBetween('receive_date', [$from, $to]);
        }

        return $query->orderByDesc('return_date')
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                $ok = $tx->details->where('judgement', TransactionDetail::JUDGEMENT_OK)->count();
                $ng = $tx->details->where('judgement', TransactionDetail::JUDGEMENT_NG)->count();
                return [
                    'id' => $tx->transaction_id,
                    'detail' => $tx->detail ?? $tx->dmc ?? $tx->line ?? '—',
                    'dmcCode' => $tx->dmc ?? '-',
                    'result' => $ng > 0 ? TransactionDetail::JUDGEMENT_NG : TransactionDetail::JUDGEMENT_OK,
                    'date' => $tx->return_date ? $tx->return_date->format('d M Y') : '-',
                    'ok' => $ok,
                    'ng' => $ng,
                ];
            });
    }

    public function getInspectorData(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $query = TransactionDetail::join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
        }

        return $query->select(
                'Internal_Users.name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN Transaction_Detail.judgement = '" . TransactionDetail::JUDGEMENT_OK . "' THEN 1 ELSE 0 END) as ok"),
                DB::raw("SUM(CASE WHEN Transaction_Detail.judgement = '" . TransactionDetail::JUDGEMENT_NG . "' THEN 1 ELSE 0 END) as ng")
            )
            ->whereNotNull('Transaction_Detail.internal_id')
            ->groupBy('Transaction_Detail.internal_id', 'Internal_Users.name')
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

    public function getFailuresByEquipment(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $query = DB::table('Transaction_Detail')
            ->join('Test_Methods', 'Transaction_Detail.method_id', '=', 'Test_Methods.method_id')
            ->leftJoin('Equipments', 'Test_Methods.equipment_id', '=', 'Equipments.equipment_id')
            ->where('Transaction_Detail.judgement', TransactionDetail::JUDGEMENT_NG);

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
        }

        return $query->select(
                DB::raw("COALESCE(Equipments.equipment_name, Test_Methods.method_name) as name"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function getTestsPerJob(?Carbon $from = null, ?Carbon $to = null): float
    {
        if ($from && $to) {
            $totalJobs = TransactionHeader::whereBetween('receive_date', [$from, $to])->count();
            $headerIds = TransactionHeader::whereBetween('receive_date', [$from, $to])->pluck('transaction_id');
            $totalTests = TransactionDetail::whereIn('transaction_id', $headerIds)->count();
        } else {
            $totalJobs = TransactionHeader::count();
            $totalTests = TransactionDetail::count();
        }
        return $totalJobs > 0 ? round($totalTests / $totalJobs, 1) : 0;
    }

    public function getInspectorEfficiency(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $durationExpression = $this->durationSecondsExpression('Transaction_Detail.start_time', 'Transaction_Detail.end_time');

        $query = TransactionDetail::join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
        }

        return $query->select('Internal_Users.name', DB::raw("AVG({$durationExpression}) as avg_seconds"))
            ->whereNotNull('Transaction_Detail.internal_id')
            ->whereNotNull('Transaction_Detail.start_time')
            ->whereNotNull('Transaction_Detail.end_time')
            ->whereRaw("{$durationExpression} > 0")
            ->whereRaw("{$durationExpression} < 3600")
            ->groupBy('Transaction_Detail.internal_id', 'Internal_Users.name')
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
