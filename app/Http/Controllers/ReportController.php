<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $dmc = $request->get('dmc');

        $results = $this->getResults($dateFrom, $dateTo, $dmc);

        return Inertia::render('Report/Index', [
            'results' => $results,
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'dmc' => $dmc],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'ids' => 'required|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'dmc' => 'nullable|string',
        ]);

        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $dmc = $request->get('dmc');

        $ids = array_filter(explode(',', $request->get('ids', '')));
        $customName = $request->get('filename', '');

        $results = $this->getResults($dateFrom, $dateTo, $dmc);

        // Filter by selected IDs if provided (not 'all')
        if (!in_array('all', $ids)) {
            $selectedIds = array_map('intval', $ids);
            $results = $results->filter(fn($r) => in_array($r->transaction_id, $selectedIds));
        }

        $filename = ($customName ?: 'QC_Report_' . $dateFrom . '_to_' . $dateTo) . '.csv';

        return new StreamedResponse(function () use ($results) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel to recognize Thai characters
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'Transaction ID',
                'Line',
                'DMC',
                'Detail',
                'Receive Date',
                'Sender',
                'Process',
                'Inspector',
                'Start Time',
                'End Time',
                'Result',
                'Remark',
            ]);

            // Data rows
            foreach ($results as $r) {
                fputcsv($handle, [
                    $r->transaction_id,
                    $r->line ?? '',
                    $r->dmc ?? '',
                    $r->detail ?? '',
                    $r->receive_date ?? '',
                    $r->sender ?? '',
                    $r->method_name ?? '',
                    $r->inspector ?? '',
                    $r->start_time ?? '',
                    $r->end_time ?? '',
                    $r->judgement ?? '',
                    $r->remark ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function getResults(string $dateFrom, string $dateTo, ?string $dmc = null)
    {
        $query = DB::table('Transaction_Detail as TD')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
            ->join('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereNull('TD.deleted_at')
            ->whereNull('TH.deleted_at')
            ->whereBetween(DB::raw('DATE(TH.receive_date)'), [$dateFrom, $dateTo])
            ->select(
                'TH.transaction_id', 'TH.dmc', 'TH.line', 'TH.receive_date',
                'EU.external_name as sender', 'TH.detail',
                'TM.method_name', 'IU.name as inspector',
                'TD.start_time', 'TD.end_time', 'TD.judgement', 'TD.remark'
            )
            ->orderByDesc('TH.receive_date');

        if ($dmc) {
            $query->where('TH.dmc', 'like', "%{$dmc}%");
        }

        return $query->get();
    }
}
