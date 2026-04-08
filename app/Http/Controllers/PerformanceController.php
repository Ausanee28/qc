<?php

namespace App\Http\Controllers;

use App\Support\ReportingConnection;
use App\Support\SchemaCapabilities;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index()
    {
        $connection = ReportingConnection::connection();
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $hasInspectorAggregate = SchemaCapabilities::hasTable('performance_daily_inspector_aggregates');
        $windowStart = now()->subDays(30);
        $windowStartDate = $windowStart->toDateString();
        $windowEndDate = now()->toDateString();

        return Inertia::render('Performance/Index', [
            'inspectors' => fn () => Cache::remember('performance.inspectors.30d', now()->addMinutes(3), function () use ($connection, $hasDetailDeletedAt, $windowStart, $hasInspectorAggregate, $windowStartDate, $windowEndDate) {
                if ($hasInspectorAggregate) {
                    return $connection->table('performance_daily_inspector_aggregates as PIA')
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
                }

                $inspectorsQuery = $connection->table('Transaction_Detail as TD')
                    ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                    ->whereNotNull('TD.start_time')
                    ->whereNotNull('TD.end_time')
                    ->where('TD.end_time', '>=', $windowStart)
                    ->select(
                        'IU.user_id as id',
                        'IU.name',
                        DB::raw('COUNT(*) as total_tests'),
                        DB::raw('ROUND(AVG(TD.duration_sec)) as avg_sec'),
                        DB::raw('MIN(TD.duration_sec) as min_sec'),
                        DB::raw('MAX(TD.duration_sec) as max_sec'),
                        DB::raw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_cnt"),
                        DB::raw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_cnt")
                    )
                    ->groupBy('IU.user_id', 'IU.name')
                    ->orderByDesc('total_tests');

                if ($hasDetailDeletedAt) {
                    $inspectorsQuery->whereNull('TD.deleted_at');
                }

                return $inspectorsQuery->get();
            }),
            'details' => fn () => Cache::remember('performance.details.30d.recent50', now()->addMinutes(3), function () use ($connection, $hasDetailDeletedAt, $hasHeaderDeletedAt, $windowStart) {
                $recentDetailIds = $connection->table('Transaction_Detail as TD')
                    ->whereNotNull('TD.start_time')
                    ->whereNotNull('TD.end_time')
                    ->where('TD.end_time', '>=', $windowStart)
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
}
