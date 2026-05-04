<?php

namespace App\Http\Controllers;

use App\Support\ReportingConnection;
use App\Support\SchemaCapabilities;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $connection = ReportingConnection::connection();
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $hasInspectorAggregate = SchemaCapabilities::hasTable('performance_daily_inspector_aggregates');
        $filters = $this->resolveFilters($request);
        $windowStart = $filters['start'];
        $windowEnd = $filters['end'];
        $windowStartDate = $windowStart->toDateString();
        $windowEndDate = $windowEnd->toDateString();
        $inspectorsCacheKey = sprintf('performance.inspectors.%s.%s.%s', $filters['mode'], $windowStart->format('YmdHis'), $windowEnd->format('YmdHis'));
        $detailsCacheKey = sprintf('performance.details.%s.%s.%s.recent50', $filters['mode'], $windowStart->format('YmdHis'), $windowEnd->format('YmdHis'));

        return Inertia::render('Performance/Index', [
            'filters' => $filters['props'],
            'inspectors' => fn () => Cache::remember($inspectorsCacheKey, now()->addMinutes(3), function () use ($connection, $hasDetailDeletedAt, $windowStart, $windowEnd, $hasInspectorAggregate, $windowStartDate, $windowEndDate) {
                $fallbackQuery = $this->buildInspectorsQueryFromDetails($connection, $windowStart, $windowEnd, $hasDetailDeletedAt);

                if ($hasInspectorAggregate && $this->hasFreshInspectorAggregate($connection, $windowStart, $windowEnd, $windowStartDate, $windowEndDate, $hasDetailDeletedAt)) {
                    $aggregateRows = $connection->table('performance_daily_inspector_aggregates as PIA')
                        ->join('Internal_Users as IU', 'PIA.internal_id', '=', 'IU.user_id')
                        ->whereBetween('PIA.date_key', [$windowStartDate, $windowEndDate])
                        ->select(
                            'IU.user_id as id',
                            'IU.name',
                            DB::raw('SUM(PIA.total_tests) as total_tests'),
                            DB::raw('ROUND(SUM(PIA.duration_total_sec) / NULLIF(SUM(PIA.duration_samples), 0)) as avg_sec'),
                            DB::raw('MIN(PIA.min_duration_sec) as min_sec'),
                            DB::raw('MAX(PIA.max_duration_sec) as max_sec'),
                            DB::raw('SUM(PIA.ok_count) as ok_cnt'),
                            DB::raw('SUM(PIA.ng_count) as ng_cnt')
                        )
                        ->groupBy('IU.user_id', 'IU.name')
                        ->orderByDesc('total_tests')
                        ->get();

                    if ($aggregateRows->isNotEmpty()) {
                        return $aggregateRows;
                    }
                }

                return $fallbackQuery->get();
            }),
            'details' => fn () => Cache::remember($detailsCacheKey, now()->addMinutes(3), function () use ($connection, $hasDetailDeletedAt, $hasHeaderDeletedAt, $windowStart, $windowEnd) {
                $recentDetailIds = $connection->table('Transaction_Detail as TD')
                    ->whereNotNull('TD.start_time')
                    ->whereNotNull('TD.end_time')
                    ->where('TD.end_time', '>=', $windowStart)
                    ->where('TD.end_time', '<=', $windowEnd)
                    ->when($hasDetailDeletedAt, fn ($query) => $query->whereNull('TD.deleted_at'))
                    ->orderByDesc('TD.end_time')
                    ->limit(50)
                    ->pluck('TD.detail_id');

                if ($recentDetailIds->isEmpty()) {
                    return collect();
                }

                $detailsQuery = $connection->table('Transaction_Detail as TD')
                    ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
                    ->whereIn('TD.detail_id', $recentDetailIds)
                    ->select(
                        'TD.detail_id',
                        'IU.name as inspector',
                        'TH.dmc',
                        'TH.line',
                        'TH.detail',
                        'TD.judgement',
                        'TD.start_time',
                        'TD.end_time',
                        'TD.duration_sec'
                    )
                    ->orderByDesc('TD.end_time');

                if ($hasDetailDeletedAt) {
                    $detailsQuery->whereNull('TD.deleted_at');
                }

                if ($hasHeaderDeletedAt) {
                    $detailsQuery->whereNull('TH.deleted_at');
                }

                return $detailsQuery->get();
            }),
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        $mode = in_array($request->query('mode'), ['recent', 'day', 'month'], true)
            ? $request->query('mode')
            : 'recent';
        $today = now();

        if ($mode === 'day') {
            $selected = $this->parseDate($request->query('date')) ?? $today->copy();
            $start = $selected->copy()->startOfDay();
            $end = $selected->copy()->endOfDay();
            $date = $selected->toDateString();

            return [
                'mode' => $mode,
                'start' => $start,
                'end' => $end,
                'props' => [
                    'mode' => $mode,
                    'date' => $date,
                    'month' => $selected->format('Y-m'),
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'label' => $selected->format('d M Y'),
                ],
            ];
        }

        if ($mode === 'month') {
            $selected = $this->parseMonth($request->query('month')) ?? $today->copy()->startOfMonth();
            $start = $selected->copy()->startOfMonth();
            $end = $selected->copy()->endOfMonth();
            $month = $selected->format('Y-m');

            return [
                'mode' => $mode,
                'start' => $start,
                'end' => $end,
                'props' => [
                    'mode' => $mode,
                    'date' => $today->toDateString(),
                    'month' => $month,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'label' => $selected->format('M Y'),
                ],
            ];
        }

        $start = $today->copy()->subDays(30)->startOfDay();
        $end = $today->copy()->endOfDay();

        return [
            'mode' => 'recent',
            'start' => $start,
            'end' => $end,
            'props' => [
                'mode' => 'recent',
                'date' => $today->toDateString(),
                'month' => $today->format('Y-m'),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'label' => 'Last 30 days',
            ],
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseMonth(?string $value): ?Carbon
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', "{$value}-01");
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildInspectorsQueryFromDetails($connection, $windowStart, $windowEnd, bool $hasDetailDeletedAt)
    {
        $durationSecondsExpr = $this->durationSecondsExpression($connection, 'TD.start_time', 'TD.end_time');
        $validDurationSecondsExpr = "NULLIF({$durationSecondsExpr}, 0)";

        $query = $connection->table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.end_time', '>=', $windowStart)
            ->where('TD.end_time', '<=', $windowEnd)
            ->select(
                'IU.user_id as id',
                'IU.name',
                DB::raw('COUNT(*) as total_tests'),
                DB::raw("ROUND(AVG({$validDurationSecondsExpr})) as avg_sec"),
                DB::raw("MIN({$validDurationSecondsExpr}) as min_sec"),
                DB::raw("MAX({$validDurationSecondsExpr}) as max_sec"),
                DB::raw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_cnt"),
                DB::raw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_cnt")
            )
            ->groupBy('IU.user_id', 'IU.name')
            ->orderByDesc('total_tests');

        if ($hasDetailDeletedAt) {
            $query->whereNull('TD.deleted_at');
        }

        return $query;
    }

    private function hasFreshInspectorAggregate($connection, $windowStart, $windowEnd, string $windowStartDate, string $windowEndDate, bool $hasDetailDeletedAt): bool
    {
        $latestAggregateDate = $connection->table('performance_daily_inspector_aggregates')
            ->whereBetween('date_key', [$windowStartDate, $windowEndDate])
            ->max('date_key');

        if ($latestAggregateDate === null) {
            return false;
        }

        $endDateExpr = $connection->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', TD.end_time)"
            : 'DATE(TD.end_time)';

        $latestDetailDateQuery = $connection->table('Transaction_Detail as TD')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.end_time', '>=', $windowStart)
            ->where('TD.end_time', '<=', $windowEnd)
            ->selectRaw("MAX({$endDateExpr}) as latest_detail_date");

        if ($hasDetailDeletedAt) {
            $latestDetailDateQuery->whereNull('TD.deleted_at');
        }

        $latestDetailDate = $latestDetailDateQuery->value('latest_detail_date');

        if ($latestDetailDate === null) {
            return false;
        }

        if ($latestDetailDate !== null && (string) $latestAggregateDate < (string) $latestDetailDate) {
            return false;
        }

        $latestPositiveAggregateDate = $connection->table('performance_daily_inspector_aggregates')
            ->whereBetween('date_key', [$windowStartDate, $windowEndDate])
            ->where('max_duration_sec', '>', 0)
            ->max('date_key');

        $durationSecondsExpr = $this->durationSecondsExpression($connection, 'TD.start_time', 'TD.end_time');
        $latestPositiveDetailDateQuery = $connection->table('Transaction_Detail as TD')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.end_time', '>=', $windowStart)
            ->where('TD.end_time', '<=', $windowEnd)
            ->whereRaw("{$durationSecondsExpr} > 0")
            ->selectRaw("MAX({$endDateExpr}) as latest_positive_detail_date");

        if ($hasDetailDeletedAt) {
            $latestPositiveDetailDateQuery->whereNull('TD.deleted_at');
        }

        $latestPositiveDetailDate = $latestPositiveDetailDateQuery->value('latest_positive_detail_date');

        if ($latestPositiveDetailDate !== null && ($latestPositiveAggregateDate === null || (string) $latestPositiveAggregateDate < (string) $latestPositiveDetailDate)) {
            return false;
        }

        return true;
    }

    private function durationSecondsExpression($connection, string $startColumn, string $endColumn): string
    {
        $driver = $connection->getDriverName();
        $diffExpr = $driver === 'sqlite'
            ? "CAST((julianday({$endColumn}) - julianday({$startColumn})) * 86400 AS INTEGER)"
            : "TIMESTAMPDIFF(SECOND, {$startColumn}, {$endColumn})";

        return "COALESCE(TD.duration_sec, {$diffExpr})";
    }
}
