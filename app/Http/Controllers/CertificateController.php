<?php

namespace App\Http\Controllers;

use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:12,24,48'],
        ]);

        $dateFrom = (string) ($filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d'));
        $dateTo = (string) ($filters['date_to'] ?? now()->format('Y-m-d'));
        $perPage = (int) ($filters['per_page'] ?? 12);
        $page = Paginator::resolveCurrentPage();
        [$fromDateTime, $toDateTime] = $this->resolveDateRange($dateFrom, $dateTo);
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');

        $jobs = Cache::remember(
            $this->certificateCacheKey($dateFrom, $dateTo, $perPage, $page),
            now()->addMinutes(3),
            function () use ($fromDateTime, $toDateTime, $perPage, $page, $hasHeaderDeletedAt, $hasDetailDeletedAt, $request) {
                $jobsQuery = DB::table('Transaction_Header as TH')
                    ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id');

                $jobsQuery->leftJoin('Transaction_Detail as TD', function ($join) use ($hasDetailDeletedAt) {
                    $join->on('TH.transaction_id', '=', 'TD.transaction_id');
                    if ($hasDetailDeletedAt) {
                        $join->whereNull('TD.deleted_at');
                    }
                });

                if ($hasHeaderDeletedAt) {
                    $jobsQuery->whereNull('TH.deleted_at');
                }

                return $jobsQuery
                    ->whereBetween('TH.receive_date', [$fromDateTime, $toDateTime])
                    ->select(
                        'TH.transaction_id',
                        'TH.dmc',
                        'TH.line',
                        'TH.receive_date',
                        'TH.return_date',
                        'EU.external_name as sender',
                        'TH.detail',
                        DB::raw('COUNT(TD.detail_id) as test_count'),
                        DB::raw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_count"),
                        DB::raw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_count")
                    )
                    ->groupBy('TH.transaction_id', 'TH.dmc', 'TH.line', 'TH.receive_date', 'TH.return_date',
                        'EU.external_name', 'TH.detail')
                    ->orderByDesc('TH.receive_date')
                    ->simplePaginate($perPage, ['*'], 'page', $page)
                    ->withPath($request->url())
                    ->appends($request->query());
            }
        );

        return Inertia::render('Certificates/Index', [
            'jobs' => $jobs,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => (string) $perPage,
            ],
        ]);
    }

    public function downloadPdf($id)
    {
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');

        $jobQuery = DB::table('Transaction_Header as TH')
            ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
            ->join('Internal_Users as IU', 'TH.internal_id', '=', 'IU.user_id')
            ->where('TH.transaction_id', $id)
            ->select('TH.*', 'EU.external_name as sender', 'IU.name as receiver');

        if ($hasHeaderDeletedAt) {
            $jobQuery->whereNull('TH.deleted_at');
        }

        $job = $jobQuery->first();

        if (!$job)
            abort(404);

        $detailsQuery = DB::table('Transaction_Detail as TD')
            ->join('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->where('TD.transaction_id', $id)
            ->select('TD.*', 'TM.method_name', 'IU.name as inspector')
            ->orderBy('TD.start_time');

        if ($hasDetailDeletedAt) {
            $detailsQuery->whereNull('TD.deleted_at');
        }

        $details = $detailsQuery->get();

        $overallJudgement = $details->contains('judgement', 'NG') ? 'NG' : 'OK';

        $pdf = Pdf::loadView('pdf.certificate', compact('job', 'details', 'overallJudgement'));
        $filename = 'QC_Report_' . ($job->dmc ?: $job->transaction_id) . '.pdf';

        return $pdf->download($filename);
    }

    private function resolveDateRange(string $dateFrom, string $dateTo): array
    {
        return [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay(),
        ];
    }

    private function certificateCacheKey(string $dateFrom, string $dateTo, int $perPage, int $page): string
    {
        return "certificates.list.{$dateFrom}.{$dateTo}.{$perPage}.{$page}";
    }
}
