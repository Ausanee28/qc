<?php

namespace App\Http\Controllers;

use App\Support\SimpleXlsxExporter;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $validatedFilters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'dmc' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|in:25,50,100,200',
        ]);

        $filters = [
            'date_from' => (string) ($validatedFilters['date_from'] ?? now()->startOfMonth()->format('Y-m-d')),
            'date_to' => (string) ($validatedFilters['date_to'] ?? now()->format('Y-m-d')),
            'dmc' => trim((string) ($validatedFilters['dmc'] ?? '')),
            'per_page' => (int) ($validatedFilters['per_page'] ?? 25),
        ];

        $summary = Cache::remember(
            $this->reportCacheKey('summary', $filters),
            now()->addMinutes(3),
            fn () => $this->buildResultsSummary($filters['date_from'], $filters['date_to'], $filters['dmc'])
        );
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageResults = Cache::remember(
            $this->reportCacheKey('page', [...$filters, 'page' => $page]),
            now()->addMinutes(3),
            fn () => $this->buildResultsQuery($filters['date_from'], $filters['date_to'], $filters['dmc'])
                ->forPage($page, $filters['per_page'])
                ->get()
        );

        $results = new LengthAwarePaginator(
            $pageResults,
            $summary['total_rows'],
            $filters['per_page'],
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page',
            ]
        );

        return Inertia::render('Report/Index', [
            'results' => $results,
            'summary' => $summary,
            'filters' => $filters,
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

        $baseFilename = $this->sanitizeFilename($customName ?: 'QC_Report_' . $dateFrom . '_to_' . $dateTo);
        $filename = str_ends_with(strtolower($baseFilename), '.xlsx') ? $baseFilename : "{$baseFilename}.xlsx";

        return new StreamedResponse(function () use ($dateFrom, $dateTo, $dmc, $ids) {
            $tempWorkbook = tempnam(sys_get_temp_dir(), 'qc-report-');

            if ($tempWorkbook === false) {
                abort(500, 'Unable to create the Excel export file.');
            }

            try {
                $rows = $this->buildExportRows($dateFrom, $dateTo, $dmc, $ids);

                (new SimpleXlsxExporter())->store(
                    $rows,
                    $tempWorkbook,
                    [16, 14, 20, 36, 16, 24, 22, 22, 12, 12, 10, 42],
                    'QC Report'
                );

                $stream = fopen($tempWorkbook, 'rb');

                if ($stream === false) {
                    abort(500, 'Unable to open the Excel export file.');
                }

                fpassthru($stream);
                fclose($stream);
            } finally {
                if (is_file($tempWorkbook)) {
                    @unlink($tempWorkbook);
                }
            }
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildResultsQuery(string $dateFrom, string $dateTo, ?string $dmc = null, ?array $transactionIds = null)
    {
        return $this->buildBaseResultsQuery($dateFrom, $dateTo, $dmc, $transactionIds)
            ->select(
                'TH.transaction_id', 'TH.dmc', 'TH.line', 'TH.receive_date',
                'EU.external_name as sender', 'TH.detail',
                'TM.method_name', 'IU.name as inspector',
                'TD.start_time', 'TD.end_time', 'TD.judgement', 'TD.remark'
            )
            ->orderByDesc('TH.receive_date');
    }

    private function buildResultsSummary(string $dateFrom, string $dateTo, ?string $dmc = null): array
    {
        $summary = $this->buildBaseResultsQuery($dateFrom, $dateTo, $dmc)
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw("SUM(CASE WHEN TD.judgement = 'OK' THEN 1 ELSE 0 END) as ok_count")
            ->selectRaw("SUM(CASE WHEN TD.judgement = 'NG' THEN 1 ELSE 0 END) as ng_count")
            ->first();

        return [
            'total_rows' => (int) ($summary->total_rows ?? 0),
            'ok_count' => (int) ($summary->ok_count ?? 0),
            'ng_count' => (int) ($summary->ng_count ?? 0),
        ];
    }

    private function buildBaseResultsQuery(string $dateFrom, string $dateTo, ?string $dmc = null, ?array $transactionIds = null)
    {
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');
        [$fromDateTime, $toDateTime] = $this->resolveDateRange($dateFrom, $dateTo);

        $query = DB::table('Transaction_Detail as TD')
            ->join('Transaction_Header as TH', 'TD.transaction_id', '=', 'TH.transaction_id')
            ->join('External_Users as EU', 'TH.external_id', '=', 'EU.external_id')
            ->join('Test_Methods as TM', 'TD.method_id', '=', 'TM.method_id')
            ->join('Internal_Users as IU', 'TD.internal_id', '=', 'IU.user_id')
            ->whereBetween('TH.receive_date', [$fromDateTime, $toDateTime]);

        if ($hasDetailDeletedAt) {
            $query->whereNull('TD.deleted_at');
        }

        if ($hasHeaderDeletedAt) {
            $query->whereNull('TH.deleted_at');
        }

        if ($dmc) {
            $query->where('TH.dmc', 'like', "%{$dmc}%");
        }

        if (is_array($transactionIds) && count($transactionIds) > 0) {
            $query->whereIn('TH.transaction_id', $transactionIds);
        }

        return $query;
    }

    private function streamResults(string $dateFrom, string $dateTo, ?string $dmc, array $ids)
    {
        $selectedIds = in_array('all', $ids, true)
            ? null
            : array_values(array_filter(array_map('intval', $ids), fn ($value) => $value > 0));

        return $this->buildResultsQuery($dateFrom, $dateTo, $dmc, $selectedIds)
            ->orderByDesc('TH.receive_date')
            ->cursor();
    }

    private function resolveDateRange(string $dateFrom, string $dateTo): array
    {
        return [
            Carbon::parse($dateFrom)->startOfDay(),
            Carbon::parse($dateTo)->endOfDay(),
        ];
    }

    private function sanitizeFilename(string $filename): string
    {
        $value = trim($filename);
        $value = preg_replace('/[\r\n]+/', ' ', $value) ?? '';
        $value = preg_replace('/[^\w\s\-.]/u', '_', $value) ?? '';
        $value = trim($value, " .\t\n\r\0\x0B");

        return $value !== '' ? $value : 'QC_Report';
    }

    private function formatExportDate(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function formatExportTime(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->format('H:i');
    }

    private function buildExportRows(string $dateFrom, string $dateTo, ?string $dmc, array $ids): array
    {
        $rows = [[
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
        ]];

        foreach ($this->streamResults($dateFrom, $dateTo, $dmc, $ids) as $r) {
            $rows[] = [
                $r->transaction_id,
                $r->line ?? '',
                $r->dmc ?? '',
                $r->detail ?? '',
                $this->formatExportDate($r->receive_date),
                $r->sender ?? '',
                $r->method_name ?? '',
                $r->inspector ?? '',
                $this->formatExportTime($r->start_time),
                $this->formatExportTime($r->end_time),
                $r->judgement ?? '',
                $r->remark ?? '',
            ];
        }

        return $rows;
    }

    private function reportCacheKey(string $segment, array $payload): string
    {
        return 'report.' . $segment . '.' . md5(json_encode($payload));
    }
}
