<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $results = DB::table('Transaction_Detail as TD')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
            ->join('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereBetween(DB::raw('DATE(TH.receive_date)'), [$dateFrom, $dateTo])
            ->select(
            'TH.transaction_id', 'TH.dmc', 'TH.line', 'TH.receive_date',
            'EU.external_name as sender', 'TH.detail',
            'TM.method_name', 'IU.name as inspector',
            'TD.start_time', 'TD.end_time', 'TD.judgement', 'TD.remark'
        )
            ->orderByDesc('TH.receive_date')
            ->get();

        return Inertia::render('Report/Index', [
            'results' => $results,
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ]);
    }
}
