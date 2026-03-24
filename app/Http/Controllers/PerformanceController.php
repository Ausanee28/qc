<?php

namespace App\Http\Controllers;

use App\Support\SchemaCapabilities;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index()
    {
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $windowStart = now()->subDays(30);

        $inspectors = Cache::remember('performance.inspectors.30d', now()->addMinutes(3), function () use ($hasDetailDeletedAt, $windowStart) {
            $inspectorsQuery = DB::table('Transaction_Detail as TD')
                ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                ->whereNotNull('TD.start_time')
                ->whereNotNull('TD.end_time')
                ->where('TD.start_time', '>=', $windowStart)
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
        });

        $details = Cache::remember('performance.details.30d.recent50', now()->addMinutes(3), function () use ($hasDetailDeletedAt, $hasHeaderDeletedAt, $windowStart) {
            $detailsQuery = DB::table('Transaction_Detail as TD')
                ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
                ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
                ->whereNotNull('TD.start_time')
                ->whereNotNull('TD.end_time')
                ->where('TD.start_time', '>=', $windowStart)
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
                ->orderByDesc('TD.end_time')
                ->limit(50);

            if ($hasDetailDeletedAt) {
                $detailsQuery->whereNull('TD.deleted_at');
            }

            if ($hasHeaderDeletedAt) {
                $detailsQuery->whereNull('TH.deleted_at');
            }

            return $detailsQuery->get();
        });

        return Inertia::render('Performance/Index', [
            'inspectors' => $inspectors,
            'details' => $details,
        ]);
    }
}
