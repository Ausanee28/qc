<?php

namespace App\Services;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Support\SchemaCapabilities;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardMetricsService
{
    private ?bool $headerHasDeletedAt = null;
    private ?bool $detailHasDeletedAt = null;

    private function hasHeaderDeletedAt(): bool
    {
        return $this->headerHasDeletedAt ??= SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
    }

    private function hasDetailDeletedAt(): bool
    {
        return $this->detailHasDeletedAt ??= SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
    }

    private function applyHeaderNotDeleted($query, string $column = 'Transaction_Header.deleted_at')
    {
        if ($this->hasHeaderDeletedAt()) {
            $query->whereNull($column);
        }

        return $query;
    }

    private function applyDetailNotDeleted($query, string $column = 'Transaction_Detail.deleted_at')
    {
        if ($this->hasDetailDeletedAt()) {
            $query->whereNull($column);
        }

        return $query;
    }

    private function headerQuery()
    {
        return $this->applyHeaderNotDeleted(
            TransactionHeader::query()->withoutGlobalScopes()
        );
    }

    private function detailQuery()
    {
        return $this->applyDetailNotDeleted(
            TransactionDetail::query()->withoutGlobalScopes()
        );
    }

    private function dateBucketExpression(string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "strftime('%Y-%m-%d', {$column})";
        }

        return "DATE({$column})";
    }

    private function monthBucketExpression(string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "strftime('%Y-%m', {$column})";
        }

        return "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function getJudgementCountsByBucket(Carbon $from, Carbon $to, string $bucketExpression): array
    {
        $query = DB::table('Transaction_Detail')
            ->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
            ->whereBetween('Transaction_Header.receive_date', [$from, $to])
            ->selectRaw("{$bucketExpression} as bucket");

        $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');
        $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');

        return $query
            ->selectRaw("SUM(CASE WHEN Transaction_Detail.judgement = ? THEN 1 ELSE 0 END) as ok", [TransactionDetail::JUDGEMENT_OK])
            ->selectRaw("SUM(CASE WHEN Transaction_Detail.judgement = ? THEN 1 ELSE 0 END) as ng", [TransactionDetail::JUDGEMENT_NG])
            ->groupBy('bucket')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->bucket => [
                    'ok' => (int) $row->ok,
                    'ng' => (int) $row->ng,
                ],
            ])
            ->all();
    }

    private function durationSecondsExpression(string $startColumn, string $endColumn): string
    {
        if (DB::getDriverName() === 'sqlite') {
            return "CAST((julianday({$endColumn}) - julianday({$startColumn})) * 86400 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(SECOND, {$startColumn}, {$endColumn})";
    }

    public function getOverviewMetrics(Carbon $from, Carbon $to): array
    {
        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfDay();

        $headerQuery = DB::table('Transaction_Header')
            ->selectRaw(
                "SUM(CASE WHEN receive_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_count,
                 SUM(CASE WHEN receive_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as month_count,
                 SUM(CASE WHEN receive_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as period_jobs,
                 SUM(CASE WHEN return_date IS NULL THEN 1 ELSE 0 END) as pending_count",
                [$todayStart, $todayEnd, $monthStart, $monthEnd, $from, $to]
            );

        $this->applyHeaderNotDeleted($headerQuery, 'Transaction_Header.deleted_at');

        $headerRow = $headerQuery->first();

        $detailQuery = DB::table('Transaction_Detail as TD')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->selectRaw(
                "SUM(CASE WHEN TH.receive_date BETWEEN ? AND ? AND TD.judgement = ? THEN 1 ELSE 0 END) as ok_count,
                 SUM(CASE WHEN TH.receive_date BETWEEN ? AND ? AND TD.judgement = ? THEN 1 ELSE 0 END) as ng_count,
                 SUM(CASE WHEN TH.receive_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as total_tests,
                 SUM(CASE WHEN TH.receive_date BETWEEN ? AND ? AND TD.judgement = ? THEN 1 ELSE 0 END) as today_ok,
                 SUM(CASE WHEN TH.receive_date BETWEEN ? AND ? AND TD.judgement = ? THEN 1 ELSE 0 END) as today_ng,
                 AVG(CASE
                        WHEN TH.receive_date BETWEEN ? AND ?
                         AND TD.start_time IS NOT NULL
                         AND TD.end_time IS NOT NULL
                        THEN TD.duration_sec
                     END) / 60 as avg_min",
                [
                    $from, $to, TransactionDetail::JUDGEMENT_OK,
                    $from, $to, TransactionDetail::JUDGEMENT_NG,
                    $from, $to,
                    $todayStart, $todayEnd, TransactionDetail::JUDGEMENT_OK,
                    $todayStart, $todayEnd, TransactionDetail::JUDGEMENT_NG,
                    $from, $to,
                ]
            );

        $this->applyDetailNotDeleted($detailQuery, 'TD.deleted_at');
        $this->applyHeaderNotDeleted($detailQuery, 'TH.deleted_at');

        $detailRow = $detailQuery->first();

        $todayCount = (int) ($headerRow->today_count ?? 0);
        $monthCount = (int) ($headerRow->month_count ?? 0);
        $periodJobs = (int) ($headerRow->period_jobs ?? 0);
        $pendingCount = (int) ($headerRow->pending_count ?? 0);
        $okCount = (int) ($detailRow->ok_count ?? 0);
        $ngCount = (int) ($detailRow->ng_count ?? 0);
        $totalTests = (int) ($detailRow->total_tests ?? 0);
        $todayOK = (int) ($detailRow->today_ok ?? 0);
        $todayNG = (int) ($detailRow->today_ng ?? 0);
        $avgTestTime = (float) round((float) ($detailRow->avg_min ?? 0));
        $yieldRate = $totalTests > 0 ? round($okCount / $totalTests * 100, 1) : 0;
        $defectRate = $totalTests > 0 ? round($ngCount / $totalTests * 100, 1) : 0;
        $testsPerJob = $periodJobs > 0 ? round($totalTests / $periodJobs, 1) : 0;

        return compact(
            'todayCount',
            'monthCount',
            'okCount',
            'ngCount',
            'pendingCount',
            'todayOK',
            'todayNG',
            'yieldRate',
            'defectRate',
            'avgTestTime',
            'totalTests',
            'testsPerJob',
        );
    }

    public function getTodayCount(): int
    {
        return $this->headerQuery()
            ->whereBetween('receive_date', [today()->startOfDay(), today()->endOfDay()])
            ->count();
    }

    public function getMonthCount(): int
    {
        return $this->headerQuery()
            ->whereBetween('receive_date', [now()->startOfMonth(), now()->endOfDay()])
            ->count();
    }

    public function getCounts(Carbon $from, Carbon $to): array
    {
        $query = DB::table('Transaction_Detail')
            ->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
            ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
        $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');
        $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');

        $row = $query->selectRaw(
                "SUM(CASE WHEN Transaction_Detail.judgement = ? THEN 1 ELSE 0 END) as ok_count,
                 SUM(CASE WHEN Transaction_Detail.judgement = ? THEN 1 ELSE 0 END) as ng_count,
                 COUNT(*) as total_tests",
                [TransactionDetail::JUDGEMENT_OK, TransactionDetail::JUDGEMENT_NG]
            )
            ->first();

        $okCount = (int) ($row->ok_count ?? 0);
        $ngCount = (int) ($row->ng_count ?? 0);
        $totalTests = (int) ($row->total_tests ?? 0);
        $yieldRate = $totalTests > 0 ? round($okCount / $totalTests * 100, 1) : 0;
        $defectRate = $totalTests > 0 ? round($ngCount / $totalTests * 100, 1) : 0;

        return compact('okCount', 'ngCount', 'totalTests', 'yieldRate', 'defectRate');
    }

    public function getTodayJudgements(): array
    {
        $query = DB::table('Transaction_Detail')
            ->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
            ->whereBetween('Transaction_Header.receive_date', [today()->startOfDay(), today()->endOfDay()]);
        $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');
        $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');

        $row = $query->selectRaw(
                "SUM(CASE WHEN Transaction_Detail.judgement = ? THEN 1 ELSE 0 END) as today_ok,
                 SUM(CASE WHEN Transaction_Detail.judgement = ? THEN 1 ELSE 0 END) as today_ng",
                [TransactionDetail::JUDGEMENT_OK, TransactionDetail::JUDGEMENT_NG]
            )
            ->first();

        $todayOK = (int) ($row->today_ok ?? 0);
        $todayNG = (int) ($row->today_ng ?? 0);

        return compact('todayOK', 'todayNG');
    }

    public function getAverageTestTimeMinutes(Carbon $from, Carbon $to): float
    {
        $query = DB::table('Transaction_Detail')
            ->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
            ->whereBetween('Transaction_Header.receive_date', [$from, $to])
            ->whereNotNull('end_time')
            ->whereNotNull('start_time');
        $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');
        $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');

        $avgTime = $query->selectRaw('AVG(Transaction_Detail.duration_sec) / 60 as avg_min')
            ->value('avg_min');

        return $avgTime ? round($avgTime) : 0;
    }

    public function getWeeklyTrend(): array
    {
        $from = now()->subDays(6)->startOfDay();
        $to = now()->endOfDay();
        $countsByDate = $this->getJudgementCountsByBucket(
            $from,
            $to,
            $this->dateBucketExpression('Transaction_Header.receive_date')
        );

        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dateKey = $day->format('Y-m-d');
            $dayCounts = $countsByDate[$dateKey] ?? ['ok' => 0, 'ng' => 0];
            $weeklyData[] = [
                'label' => $day->format('D d/m'),
                'ok' => $dayCounts['ok'],
                'ng' => $dayCounts['ng'],
            ];
        }

        return $weeklyData;
    }

    public function getDailyTrend(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfToday = now()->endOfDay();
        $countsByDate = $this->getJudgementCountsByBucket(
            $startOfMonth,
            $endOfToday,
            $this->dateBucketExpression('Transaction_Header.receive_date')
        );

        $days = [];

        for ($d = $startOfMonth->copy(); $d->lte(now()); $d->addDay()) {
            $date = $d->format('Y-m-d');
            $dayCounts = $countsByDate[$date] ?? ['ok' => 0, 'ng' => 0];
            $days[] = [
                'label' => $d->format('d M'),
                'ok' => $dayCounts['ok'],
                'ng' => $dayCounts['ng'],
            ];
        }

        return $days;
    }

    public function getMonthlyTrend(): array
    {
        $from = now()->subMonths(5)->startOfMonth();
        $to = now()->endOfMonth();
        $countsByMonth = $this->getJudgementCountsByBucket(
            $from,
            $to,
            $this->monthBucketExpression('Transaction_Header.receive_date')
        );

        $monthlyRaw = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthCounts = $countsByMonth[$m->format('Y-m')] ?? ['ok' => 0, 'ng' => 0];
            $ok = $monthCounts['ok'];
            $ng = $monthCounts['ng'];

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

        $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
            $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');
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
        $query = DB::table('Transaction_Header as TH')
            ->leftJoin('Transaction_Detail as TD', function ($join) {
                $join->on('TH.transaction_id', '=', 'TD.transaction_id');

                if ($this->hasDetailDeletedAt()) {
                    $join->whereNull('TD.deleted_at');
                }
            })
            ->whereNotNull('TH.return_date');

        $this->applyHeaderNotDeleted($query, 'TH.deleted_at');

        if ($from && $to) {
            $query->whereBetween('TH.receive_date', [$from, $to]);
        }

        return $query->select(
                'TH.transaction_id as id',
                'TH.detail',
                'TH.dmc',
                'TH.line',
                'TH.return_date',
                DB::raw("SUM(CASE WHEN TD.judgement = '" . TransactionDetail::JUDGEMENT_OK . "' THEN 1 ELSE 0 END) as ok"),
                DB::raw("SUM(CASE WHEN TD.judgement = '" . TransactionDetail::JUDGEMENT_NG . "' THEN 1 ELSE 0 END) as ng")
            )
            ->groupBy('TH.transaction_id', 'TH.detail', 'TH.dmc', 'TH.line', 'TH.return_date')
            ->orderByDesc('TH.return_date')
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                $ok = (int) ($tx->ok ?? 0);
                $ng = (int) ($tx->ng ?? 0);

                return [
                    'id' => $tx->id,
                    'detail' => $tx->detail ?? $tx->dmc ?? $tx->line ?? '-',
                    'dmcCode' => $tx->dmc ?? '-',
                    'result' => $ng > 0 ? TransactionDetail::JUDGEMENT_NG : TransactionDetail::JUDGEMENT_OK,
                    'date' => $tx->return_date ? Carbon::parse($tx->return_date)->format('d M Y') : '-',
                    'ok' => $ok,
                    'ng' => $ng,
                ];
            });
    }

    public function getInspectorData(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $query = $this->detailQuery()
            ->join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
            $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');
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
        $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
            $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');
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
            $totalJobs = $this->headerQuery()->whereBetween('receive_date', [$from, $to])->count();
            $query = DB::table('Transaction_Detail')
                ->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
            $this->applyDetailNotDeleted($query, 'Transaction_Detail.deleted_at');
            $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');
            $totalTests = $query->count();
        } else {
            $totalJobs = $this->headerQuery()->count();
            $totalTests = $this->detailQuery()->count();
        }
        return $totalJobs > 0 ? round($totalTests / $totalJobs, 1) : 0;
    }

    public function getInspectorEfficiency(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): \Illuminate\Support\Collection
    {
        $durationExpression = $this->durationSecondsExpression('Transaction_Detail.start_time', 'Transaction_Detail.end_time');

        $query = $this->detailQuery()
            ->join('Internal_Users', 'Transaction_Detail.internal_id', '=', 'Internal_Users.user_id');

        if ($from && $to) {
            $query->join('Transaction_Header', 'Transaction_Detail.transaction_id', '=', 'Transaction_Header.transaction_id')
                ->whereBetween('Transaction_Header.receive_date', [$from, $to]);
            $this->applyHeaderNotDeleted($query, 'Transaction_Header.deleted_at');
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
