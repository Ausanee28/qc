<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index()
    {
        $inspectors = DB::table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereNull('TD.deleted_at')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.start_time', '>=', now()->subDays(30))
            ->select(
            'IU.user_id as id', 'IU.name',
            DB::raw('COUNT(*) as total_tests'),
            DB::raw('ROUND(AVG(TD.duration_sec)) as avg_sec'),
            DB::raw('MIN(TD.duration_sec) as min_sec'),
            DB::raw('MAX(TD.duration_sec) as max_sec'),
            DB::raw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_cnt"),
            DB::raw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_cnt")
        )
            ->groupBy('IU.user_id', 'IU.name')
            ->orderByDesc('total_tests')
            ->get();

        $details = DB::table('Transaction_Detail as TD')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->whereNull('TD.deleted_at')
            ->whereNull('TH.deleted_at')
            ->whereNotNull('TD.start_time')
            ->whereNotNull('TD.end_time')
            ->where('TD.start_time', '>=', now()->subDays(30))
            ->select(
            'TD.detail_id', 'IU.name as inspector', 'TH.dmc', 'TH.line',
            'TH.detail', 'TD.judgement', 'TD.start_time', 'TD.end_time',
            'TD.duration_sec'
        )
            ->orderByDesc('TD.end_time')
            ->limit(50)
            ->get();

        return Inertia::render('Performance/Index', [
            'inspectors' => $inspectors,
            'details' => $details,
        ]);
    }
}
