<?php

namespace App\Http\Controllers;

use App\Support\SimpleXlsxExporter;
use App\Support\TemplateXlsxExporter;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

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

        $page = LengthAwarePaginator::resolveCurrentPage();

        return Inertia::render('Report/Index', [
            'filters' => $filters,
            'exportTemplates' => fn () => $this->availableExportTemplates(),
            'zipAvailable' => fn () => $this->zipExtensionAvailable(),
            'results' => fn () => (function () use ($filters, $page, $request) {
                $summary = Cache::remember(
                    $this->reportCacheKey('summary', $filters),
                    now()->addMinutes(3),
                    fn () => $this->buildResultsSummary($filters['date_from'], $filters['date_to'], $filters['dmc'])
                );

                $pageResults = Cache::remember(
                    $this->reportCacheKey('page', [...$filters, 'page' => $page]),
                    now()->addMinutes(3),
                    fn () => $this->buildResultsQuery($filters['date_from'], $filters['date_to'], $filters['dmc'])
                        ->forPage($page, $filters['per_page'])
                        ->get()
                );

                return new LengthAwarePaginator(
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
            })(),
            'summary' => fn () => Cache::remember(
                $this->reportCacheKey('summary', $filters),
                now()->addMinutes(3),
                fn () => $this->buildResultsSummary($filters['date_from'], $filters['date_to'], $filters['dmc'])
            ),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'ids' => 'required|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'dmc' => 'nullable|string',
            'template_key' => 'nullable|string|max:255',
            'template_sheet' => 'nullable|string|max:120',
        ]);

        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $dmc = $request->get('dmc');

        $ids = array_filter(explode(',', $request->get('ids', '')));
        $customName = $request->get('filename', '');
        $templateKey = trim((string) $request->get('template_key', 'standard'));
        $templateConfig = $this->resolveTemplateConfig($templateKey);
        $templateSheet = trim((string) $request->get('template_sheet', ''));

        if ($templateSheet !== '') {
            $templateConfig['sheet_name'] = $templateSheet;
        }

        $includeHeader = $templateConfig['include_header'] ?? true;

        $baseFilename = $this->sanitizeFilename($customName ?: 'QC_Report_' . $dateFrom . '_to_' . $dateTo);
        $filename = str_ends_with(strtolower($baseFilename), '.xlsx') ? $baseFilename : "{$baseFilename}.xlsx";

        return new StreamedResponse(function () use ($dateFrom, $dateTo, $dmc, $ids, $templateConfig, $includeHeader) {
            $tempWorkbook = tempnam(sys_get_temp_dir(), 'qc-report-');

            if ($tempWorkbook === false) {
                abort(500, 'Unable to create the Excel export file.');
            }

            try {
                $rows = $this->buildExportRows($dateFrom, $dateTo, $dmc, $ids, $includeHeader);

                if (($templateConfig['path'] ?? null) !== null) {
                    if (!$this->zipExtensionAvailable()) {
                        abort(422, 'Template export requires PHP ZIP extension (ext-zip).');
                    }

                    (new TemplateXlsxExporter())->store(
                        $rows,
                        $templateConfig['path'],
                        $tempWorkbook,
                        [
                            'marker' => $templateConfig['marker'] ?? '{{DATA_TABLE}}',
                            'sheet_name' => $templateConfig['sheet_name'] ?? null,
                            'start_cell' => $templateConfig['start_cell'] ?? null,
                        ]
                    );
                } else {
                    (new SimpleXlsxExporter())->store(
                        $rows,
                        $tempWorkbook,
                        [16, 14, 20, 36, 16, 24, 22, 22, 12, 12, 10, 42],
                        'QC Report'
                    );
                }

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

    public function templates()
    {
        return Inertia::render('Report/Templates', [
            'templates' => $this->listTemplateConfigurations(),
            'zipAvailable' => $this->zipExtensionAvailable(),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:120',
            'file' => 'required|file|mimes:xlsx|max:10240',
            'marker' => 'nullable|string|max:120',
            'sheet_name' => 'nullable|string|max:120',
            'start_cell' => ['nullable', 'regex:/^[A-Za-z]{1,3}[1-9][0-9]{0,6}$/'],
            'include_header' => 'nullable|boolean',
        ]);

        $directory = $this->ensureTemplatesDirectory();
        $id = 'tpl_' . Str::lower(Str::random(10));
        $fileName = $id . '.xlsx';

        $request->file('file')->move($directory, $fileName);

        $metadata = [
            'id' => $id,
            'label' => trim((string) $validated['label']),
            'file_name' => $fileName,
            'marker' => trim((string) ($validated['marker'] ?? '{{DATA_TABLE}}')) ?: '{{DATA_TABLE}}',
            'sheet_name' => ($sheetName = trim((string) ($validated['sheet_name'] ?? ''))) !== '' ? $sheetName : null,
            'start_cell' => ($startCell = trim((string) ($validated['start_cell'] ?? ''))) !== '' ? strtoupper($startCell) : null,
            'include_header' => (bool) ($validated['include_header'] ?? false),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $this->writeTemplateMetadata($id, $metadata);

        return redirect()
            ->route('report.templates.index')
            ->with('success', 'Excel template uploaded successfully.');
    }

    public function updateTemplate(Request $request, string $templateId)
    {
        $metadata = $this->readTemplateMetadata($templateId);

        abort_if($metadata === null, 404);

        $validated = $request->validate([
            'label' => 'required|string|max:120',
            'marker' => 'nullable|string|max:120',
            'sheet_name' => 'nullable|string|max:120',
            'start_cell' => ['nullable', 'regex:/^[A-Za-z]{1,3}[1-9][0-9]{0,6}$/'],
            'include_header' => 'nullable|boolean',
        ]);

        $metadata['label'] = trim((string) $validated['label']);
        $metadata['marker'] = trim((string) ($validated['marker'] ?? '{{DATA_TABLE}}')) ?: '{{DATA_TABLE}}';
        $metadata['sheet_name'] = ($sheetName = trim((string) ($validated['sheet_name'] ?? ''))) !== '' ? $sheetName : null;
        $metadata['start_cell'] = ($startCell = trim((string) ($validated['start_cell'] ?? ''))) !== '' ? strtoupper($startCell) : null;
        $metadata['include_header'] = (bool) ($validated['include_header'] ?? false);
        $metadata['updated_at'] = now()->toIso8601String();

        $this->writeTemplateMetadata($templateId, $metadata);

        return redirect()
            ->route('report.templates.index')
            ->with('success', 'Template settings updated.');
    }

    public function destroyTemplate(string $templateId)
    {
        $templateConfig = $this->findTemplateConfigurationById($templateId);

        abort_if($templateConfig === null, 404);

        if (is_file($templateConfig['path'])) {
            @unlink($templateConfig['path']);
        }

        $metadataPath = $this->templateMetadataPath($templateId);

        if (is_file($metadataPath)) {
            @unlink($metadataPath);
        }

        return redirect()
            ->route('report.templates.index')
            ->with('success', 'Template deleted.');
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

    private function buildExportRows(string $dateFrom, string $dateTo, ?string $dmc, array $ids, bool $includeHeader = true): array
    {
        $rows = [];

        if ($includeHeader) {
            $rows[] = [
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
            ];
        }

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

    private function availableExportTemplates(): array
    {
        $templates = [[
            'key' => 'standard',
            'label' => 'Standard Table',
            'description' => 'Default export generated by the system.',
        ]];

        if (!$this->zipExtensionAvailable()) {
            return $templates;
        }

        foreach ($this->listTemplateConfigurations() as $template) {
            $templates[] = [
                'key' => 'template:' . $template['id'],
                'label' => $template['label'],
                'description' => $template['description'],
                'sheet_names' => $template['analysis']['sheet_names'] ?? [],
                'default_sheet_name' => $template['sheet_name'] ?? null,
            ];
        }

        return $templates;
    }

    private function resolveTemplateConfig(string $templateKey): array
    {
        if ($templateKey === '' || $templateKey === 'standard') {
            return [
                'path' => null,
                'marker' => '{{DATA_TABLE}}',
                'sheet_name' => null,
                'start_cell' => null,
                'include_header' => true,
            ];
        }

        if (!str_starts_with($templateKey, 'template:')) {
            return [
                'path' => null,
                'marker' => '{{DATA_TABLE}}',
                'sheet_name' => null,
                'start_cell' => null,
                'include_header' => true,
            ];
        }

        $templateId = basename(substr($templateKey, strlen('template:')));
        $templateConfig = $this->findTemplateConfigurationById($templateId);

        if ($templateConfig === null) {
            return [
                'path' => null,
                'marker' => '{{DATA_TABLE}}',
                'sheet_name' => null,
                'start_cell' => null,
                'include_header' => true,
            ];
        }

        return [
            'path' => $templateConfig['path'],
            'marker' => $templateConfig['marker'] ?: '{{DATA_TABLE}}',
            'sheet_name' => $templateConfig['sheet_name'],
            'start_cell' => $templateConfig['start_cell'],
            'include_header' => (bool) $templateConfig['include_header'],
        ];
    }

    private function listTemplateConfigurations(): array
    {
        $directory = $this->ensureTemplatesDirectory();
        $templates = [];

        $metadataFiles = glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($metadataFiles);

        foreach ($metadataFiles as $metadataPath) {
            $id = pathinfo($metadataPath, PATHINFO_FILENAME);
            $metadata = $this->readTemplateMetadata($id);

            if (!is_array($metadata)) {
                continue;
            }

            $fileName = basename((string) ($metadata['file_name'] ?? ''));
            $path = $directory . DIRECTORY_SEPARATOR . $fileName;

            if ($fileName === '' || !is_file($path)) {
                continue;
            }

            $analysis = $this->inspectTemplateWorkbook($path, (string) ($metadata['marker'] ?? '{{DATA_TABLE}}'));
            $mode = !empty($metadata['start_cell']) ? "Start cell {$metadata['start_cell']}" : "Marker " . ((string) ($metadata['marker'] ?? '{{DATA_TABLE}}'));

            $templates[] = [
                'id' => $id,
                'label' => (string) ($metadata['label'] ?? Str::headline($id)),
                'description' => 'Template file: ' . $fileName . ' (' . $mode . ')',
                'file_name' => $fileName,
                'path' => $path,
                'marker' => (string) ($metadata['marker'] ?? '{{DATA_TABLE}}'),
                'sheet_name' => $metadata['sheet_name'] ?? null,
                'start_cell' => $metadata['start_cell'] ?? null,
                'include_header' => (bool) ($metadata['include_header'] ?? false),
                'analysis' => $analysis,
                'created_at' => $metadata['created_at'] ?? null,
                'updated_at' => $metadata['updated_at'] ?? null,
            ];
        }

        $managedFileNames = collect($templates)->pluck('file_name')->all();
        $xlsxFiles = glob($directory . DIRECTORY_SEPARATOR . '*.xlsx') ?: [];
        sort($xlsxFiles);

        foreach ($xlsxFiles as $xlsxPath) {
            $fileName = basename($xlsxPath);

            if (in_array($fileName, $managedFileNames, true)) {
                continue;
            }

            $id = pathinfo($fileName, PATHINFO_FILENAME);
            $analysis = $this->inspectTemplateWorkbook($xlsxPath, '{{DATA_TABLE}}');

            $templates[] = [
                'id' => $id,
                'label' => Str::headline($id),
                'description' => 'Template file: ' . $fileName . ' (Marker {{DATA_TABLE}})',
                'file_name' => $fileName,
                'path' => $xlsxPath,
                'marker' => '{{DATA_TABLE}}',
                'sheet_name' => null,
                'start_cell' => null,
                'include_header' => false,
                'analysis' => $analysis,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        usort($templates, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $templates;
    }

    private function findTemplateConfigurationById(string $templateId): ?array
    {
        $id = basename($templateId);

        foreach ($this->listTemplateConfigurations() as $template) {
            if (($template['id'] ?? null) === $id) {
                return $template;
            }
        }

        return null;
    }

    private function templatesDirectory(): string
    {
        return storage_path('app/report-templates');
    }

    private function ensureTemplatesDirectory(): string
    {
        $directory = $this->templatesDirectory();
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    private function templateMetadataPath(string $templateId): string
    {
        $safeTemplateId = basename($templateId);

        return $this->templatesDirectory() . DIRECTORY_SEPARATOR . $safeTemplateId . '.json';
    }

    private function readTemplateMetadata(string $templateId): ?array
    {
        $metadataPath = $this->templateMetadataPath($templateId);

        if (!is_file($metadataPath)) {
            return null;
        }

        $contents = file_get_contents($metadataPath);

        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function writeTemplateMetadata(string $templateId, array $metadata): void
    {
        $metadataPath = $this->templateMetadataPath($templateId);
        file_put_contents($metadataPath, json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function inspectTemplateWorkbook(string $templatePath, ?string $marker): array
    {
        if (!$this->zipExtensionAvailable()) {
            return [
                'sheet_names' => [],
                'marker_found' => false,
                'file_size_kb' => round((filesize($templatePath) ?: 0) / 1024, 1),
                'zip_available' => false,
            ];
        }

        $zip = new ZipArchive();

        if ($zip->open($templatePath) !== true) {
            return [
                'sheet_names' => [],
                'marker_found' => false,
                'file_size_kb' => round((filesize($templatePath) ?: 0) / 1024, 1),
            ];
        }

        $sheetNames = [];
        $markerFound = false;
        $needle = trim((string) ($marker ?? ''));

        try {
            $workbookXml = $zip->getFromName('xl/workbook.xml');

            if ($workbookXml !== false) {
                $dom = new \DOMDocument();

                if (@$dom->loadXML($workbookXml)) {
                    $xpath = new \DOMXPath($dom);
                    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

                    foreach ($xpath->query('/m:workbook/m:sheets/m:sheet') ?: [] as $sheetNode) {
                        if ($sheetNode instanceof \DOMElement) {
                            $sheetNames[] = (string) $sheetNode->getAttribute('name');
                        }
                    }
                }
            }

            if ($needle !== '') {
                for ($index = 1; $index <= $zip->numFiles; $index++) {
                    $fileName = $zip->getNameIndex($index);

                    if (!is_string($fileName) || !str_starts_with($fileName, 'xl/worksheets/sheet') || !str_ends_with($fileName, '.xml')) {
                        continue;
                    }

                    $sheetXml = $zip->getFromName($fileName);

                    if (is_string($sheetXml) && str_contains($sheetXml, $needle)) {
                        $markerFound = true;
                        break;
                    }
                }
            }
        } finally {
            $zip->close();
        }

        return [
            'sheet_names' => $sheetNames,
            'marker_found' => $markerFound,
            'file_size_kb' => round((filesize($templatePath) ?: 0) / 1024, 1),
            'zip_available' => true,
        ];
    }

    private function zipExtensionAvailable(): bool
    {
        return class_exists('ZipArchive');
    }

    private function reportCacheKey(string $segment, array $payload): string
    {
        return 'report.' . $segment . '.' . md5(json_encode($payload));
    }
}
