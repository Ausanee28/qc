<?php

namespace App\Http\Controllers;

use App\Events\DashboardDataChanged;
use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\TestMethod;
use App\Models\User;
use App\Support\PendingJobsVersion;
use App\Support\DashboardCache;
use App\Support\AuditLogger;
use App\Support\SearchTerm;
use App\Support\SchemaCapabilities;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExecuteTestController extends Controller
{
    private const DEFAULT_RESULTS_CACHE_KEY = 'execute_test.results.default.active.per_page_20';
    private const PENDING_JOBS_WINDOW = 500;
    private const PENDING_JOBS_PAGE_SIZE = 50;

    public function create(Request $request)
    {
        $supportsDetailSoftDeletes = TransactionDetail::supportsSoftDeletes();
        $currentPage = max(1, (int) $request->integer('page', 1));

        $validatedFilters = $request->validate([
            'search' => 'nullable|string|max:255',
            'judgement' => 'nullable|in:all,OK,NG',
            'record_state' => 'nullable|in:active,deleted,all',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,20,50,100',
        ]);

        $filters = [
            'search' => trim((string) ($validatedFilters['search'] ?? '')),
            'judgement' => (string) ($validatedFilters['judgement'] ?? 'all'),
            'record_state' => (string) ($validatedFilters['record_state'] ?? 'active'),
            'date_from' => (string) ($validatedFilters['date_from'] ?? ''),
            'date_to' => (string) ($validatedFilters['date_to'] ?? ''),
            'per_page' => (int) ($validatedFilters['per_page'] ?? 20),
        ];

        if (!$supportsDetailSoftDeletes && $filters['record_state'] !== 'active') {
            $filters['record_state'] = 'active';
        }

        return Inertia::render('ExecuteTest/Create', [
            'pendingJobs' => fn () => Cache::remember('execute_test.pending_jobs.active', now()->addSeconds(30), function () {
                return $this->pendingJobsQuery('')
                    ->orderByDesc('receive_date')
                    ->limit(self::PENDING_JOBS_WINDOW)
                    ->get(['Transaction_Header.transaction_id', 'Transaction_Header.dmc', 'Transaction_Header.line', 'Transaction_Header.detail', 'Transaction_Header.sender_leader', 'EU.external_name'])
                    ->map(fn ($job) => [
                        'transaction_id' => $job->transaction_id,
                        'dmc' => $job->dmc,
                        'line' => $job->line,
                        'detail' => $job->detail,
                        'sender_name' => $job->external_name === 'อื่นๆ (Other)' ? ($job->sender_leader ?: 'Unknown Leader') : $job->external_name,
                    ]);
            }),
            'pendingJobsCount' => fn () => Cache::remember('execute_test.pending_jobs_count.active', now()->addSeconds(30), fn () => TransactionHeader::whereNull('return_date')->count()),
            'pendingJobsWindow' => self::PENDING_JOBS_WINDOW,
            'pendingJobsPageSize' => self::PENDING_JOBS_PAGE_SIZE,
            'pendingJobsVersion' => fn () => $this->pendingJobsVersionToken(),
            'methods' => fn () => Cache::remember('execute_test.methods', now()->addMinutes(10), fn () => TestMethod::query()
                ->when(SchemaCapabilities::hasColumn('Test_Methods', 'is_active'), fn ($query) => $query->where('is_active', true))
                ->orderBy('method_name')
                ->get()),
            'inspectors' => fn () => Cache::remember('execute_test.inspectors', now()->addMinutes(10), fn () => User::query()
                ->when(SchemaCapabilities::hasColumn('Internal_Users', 'is_active'), fn ($query) => $query->where('is_active', true))
                ->orderBy('name')
                ->get(['user_id', 'name'])),
            'results' => fn () => $this->resolveResultsPayload($filters, $supportsDetailSoftDeletes, $currentPage),
            'filters' => $filters,
        ]);
    }

    public function pendingJobsVersion(): JsonResponse
    {
        return response()->json([
            'version' => $this->pendingJobsVersionToken(),
        ]);
    }

    public function pendingJobs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|in:20,50',
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $perPage = (int) ($validated['per_page'] ?? self::PENDING_JOBS_PAGE_SIZE);

        $paginator = $this->pendingJobsQuery($search)
            ->orderByDesc('Transaction_Header.receive_date')
            ->simplePaginate($perPage, ['Transaction_Header.transaction_id', 'Transaction_Header.dmc', 'Transaction_Header.line', 'Transaction_Header.detail', 'Transaction_Header.sender_leader', 'EU.external_name'], 'page', $page);

        return response()->json([
            'items' => $paginator->getCollection()->map(fn ($job) => [
                'transaction_id' => $job->transaction_id,
                'dmc' => $job->dmc,
                'line' => $job->line,
                'detail' => $job->detail,
                'sender_name' => $job->external_name === 'อื่นๆ (Other)' ? ($job->sender_leader ?: 'Unknown Leader') : $job->external_name,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'search' => $search,
                'open_jobs_count' => Cache::remember(
                    'execute_test.pending_jobs_count.active',
                    now()->addSeconds(30),
                    fn () => TransactionHeader::whereNull('return_date')->count()
                ),
            ],
            'version' => $this->pendingJobsVersionToken(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        [$startDt, $endDt, $durationSec] = $this->normalizeTimes($validated);
        $detail = null;

        DB::transaction(function () use ($validated, $startDt, $endDt, $durationSec, &$detail) {
            $detail = TransactionDetail::create([
                'transaction_id' => $validated['transaction_id'],
                'method_id' => $validated['method_id'],
                'internal_id' => $validated['internal_id'],
                'start_time' => $startDt,
                'end_time' => $endDt,
                'duration_sec' => $durationSec,
                'max_value' => $this->normalizeOptionalText($validated['max_value'] ?? null),
                'min_value' => $this->normalizeOptionalText($validated['min_value'] ?? null),
                'judgement' => $validated['judgement'],
                'remark' => $validated['remark'] ?? null,
            ]);
        });

        if ($detail !== null) {
            AuditLogger::log(
                'execute_test',
                'create',
                'Transaction_Detail',
                $detail->detail_id,
                null,
                $this->detailAuditPayload($detail)
            );
        }

        $this->forgetPerformanceCaches();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('execute-test.create')
            ->with('success', "Test result recorded for Job #{$validated['transaction_id']}!");
    }

    public function update(Request $request, int $id)
    {
        $detail = TransactionDetail::findOrFail($id);
        $beforeData = $this->detailAuditPayload($detail);
        $validated = $this->validatePayload($request);
        [$startDt, $endDt, $durationSec] = $this->normalizeTimes($validated);

        $detail->update([
            'transaction_id' => $validated['transaction_id'],
            'method_id' => $validated['method_id'],
            'internal_id' => $validated['internal_id'],
            'start_time' => $startDt,
            'end_time' => $endDt,
            'duration_sec' => $durationSec,
            'max_value' => $this->normalizeOptionalText($validated['max_value'] ?? null),
            'min_value' => $this->normalizeOptionalText($validated['min_value'] ?? null),
            'judgement' => $validated['judgement'],
            'remark' => $validated['remark'] ?? null,
        ]);
        $detail->refresh();
        AuditLogger::log(
            'execute_test',
            'update',
            'Transaction_Detail',
            $detail->detail_id,
            $beforeData,
            $this->detailAuditPayload($detail)
        );
        $this->forgetPerformanceCaches();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('execute-test.create')
            ->with('success', "Test result #{$detail->detail_id} updated successfully!");
    }

    public function destroy(int $id)
    {
        if (auth()->user()?->role !== 'admin') {
            return response('Forbidden.', 403);
        }

        $detail = TransactionDetail::findOrFail($id);
        $beforeData = $this->detailAuditPayload($detail);
        $detail->delete();

        $afterData = null;
        if (TransactionDetail::supportsSoftDeletes()) {
            $deletedDetail = TransactionDetail::withTrashed()->find($id);
            $afterData = $deletedDetail ? $this->detailAuditPayload($deletedDetail) : null;
        }

        AuditLogger::log(
            'execute_test',
            'delete',
            'Transaction_Detail',
            $id,
            $beforeData,
            $afterData
        );
        $this->forgetPerformanceCaches();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('execute-test.create')
            ->with('success', "Test result #{$id} deleted successfully!");
    }

    public function restore(int $id)
    {
        if (auth()->user()?->role !== 'admin') {
            return response('Forbidden.', 403);
        }

        if (!TransactionDetail::supportsSoftDeletes()) {
            return redirect()->route('execute-test.create')
                ->with('error', 'Restore is unavailable because this database does not support soft deletes.');
        }

        $detail = TransactionDetail::onlyTrashed()->findOrFail($id);
        $beforeData = $this->detailAuditPayload($detail);
        $detail->restore();

        $restoredDetail = TransactionDetail::withTrashed()->find($id);
        AuditLogger::log(
            'execute_test',
            'restore',
            'Transaction_Detail',
            $id,
            $beforeData,
            $restoredDetail ? $this->detailAuditPayload($restoredDetail) : null
        );
        $this->forgetPerformanceCaches();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('execute-test.create')
            ->with('success', "Test result #{$id} restored successfully!");
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'transaction_id' => 'required|exists:Transaction_Header,transaction_id',
            'method_id' => 'required|exists:Test_Methods,method_id',
            'internal_id' => 'required|exists:Internal_Users,user_id',
            'judgement' => 'required|in:' . \App\Models\TransactionDetail::JUDGEMENT_OK . ',' . \App\Models\TransactionDetail::JUDGEMENT_NG,
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_date' => 'nullable|date',
            'end_time' => 'nullable',
            'max_value' => 'nullable|string|max:255',
            'min_value' => 'nullable|string|max:255',
            'remark' => 'nullable|string|max:255',
        ]);

        $job = TransactionHeader::find($validated['transaction_id']);

        if (!$job || $job->return_date !== null) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Selected job is not open for test execution.',
            ]);
        }

        if (SchemaCapabilities::hasColumn('Internal_Users', 'is_active')) {
            $isActiveInspector = User::query()
                ->where('user_id', (int) $validated['internal_id'])
                ->where('is_active', true)
                ->exists();

            if (!$isActiveInspector) {
                throw ValidationException::withMessages([
                    'internal_id' => 'Selected inspector is inactive.',
                ]);
            }
        }

        return $validated;
    }

    private function normalizeTimes(array $validated): array
    {
        $startDt = $validated['start_date'] . ' ' . $validated['start_time'] . ':00';
        $endDt = ($validated['end_date'] ?? null) && ($validated['end_time'] ?? null)
            ? $validated['end_date'] . ' ' . $validated['end_time'] . ':00'
            : null;

        if ($endDt && strtotime($endDt) < strtotime($startDt)) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $durationSec = $endDt ? strtotime($endDt) - strtotime($startDt) : null;

        return [$startDt, $endDt, $durationSec];
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function pendingJobsVersionToken(): string
    {
        return PendingJobsVersion::current();
    }

    private function pendingJobsQuery(string $search)
    {
        $query = TransactionHeader::query()
            ->leftJoin('External_Users as EU', 'Transaction_Header.external_id', '=', 'EU.external_id')
            ->select('Transaction_Header.*', 'EU.external_name')
            ->whereNull('Transaction_Header.return_date');

        if ($search === '') {
            return $query;
        }

        if (ctype_digit($search)) {
            return $query->where('Transaction_Header.transaction_id', (int) $search);
        }

        if (SearchTerm::canUseFullText($search)) {
            $term = SearchTerm::toBooleanTerm($search);

            if ($term !== '') {
                return $query->where(function ($subQuery) use ($term, $search) {
                    $subQuery
                        ->whereRaw("MATCH(Transaction_Header.detail, Transaction_Header.dmc, Transaction_Header.line) AGAINST (? IN BOOLEAN MODE)", [$term])
                        ->orWhere('Transaction_Header.dmc', 'like', "%{$search}%")
                        ->orWhere('Transaction_Header.line', 'like', "%{$search}%")
                        ->orWhere('Transaction_Header.detail', 'like', "%{$search}%")
                        ->orWhere('EU.external_name', 'like', "%{$search}%")
                        ->orWhere('Transaction_Header.sender_leader', 'like', "%{$search}%");
                });
            }
        }

        return $query->where(function ($subQuery) use ($search) {
            $subQuery
                ->where('Transaction_Header.dmc', 'like', "%{$search}%")
                ->orWhere('Transaction_Header.line', 'like', "%{$search}%")
                ->orWhere('Transaction_Header.detail', 'like', "%{$search}%")
                ->orWhere('EU.external_name', 'like', "%{$search}%")
                ->orWhere('Transaction_Header.sender_leader', 'like', "%{$search}%");
        });
    }

    private function forgetPerformanceCaches(): void
    {
        Cache::forget('execute_test.pending_jobs.active');
        Cache::forget('execute_test.pending_jobs_count.active');
        Cache::forget('performance.inspectors.30d');
        Cache::forget('performance.details.30d.recent50');
        Cache::forget(self::DEFAULT_RESULTS_CACHE_KEY);
    }

    private function resolveResultsPayload(array $filters, bool $supportsDetailSoftDeletes, int $currentPage)
    {
        if ($this->shouldCacheDefaultResultsPayload($filters, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_RESULTS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildResultsPayload($filters, $supportsDetailSoftDeletes)
            );
        }

        return $this->buildResultsPayload($filters, $supportsDetailSoftDeletes);
    }

    private function shouldCacheDefaultResultsPayload(array $filters, int $currentPage): bool
    {
        return $currentPage === 1
            && $filters['search'] === ''
            && $filters['judgement'] === 'all'
            && $filters['record_state'] === 'active'
            && $filters['date_from'] === ''
            && $filters['date_to'] === ''
            && $filters['per_page'] === 20;
    }

    private function buildResultsPayload(array $filters, bool $supportsDetailSoftDeletes)
    {
        return TransactionDetail::query()
            ->when($supportsDetailSoftDeletes && $filters['record_state'] === 'all', fn ($query) => $query->withTrashed())
            ->when($supportsDetailSoftDeletes && $filters['record_state'] === 'deleted', fn ($query) => $query->onlyTrashed())
            ->leftJoin('Transaction_Header as TH', 'Transaction_Detail.transaction_id', '=', 'TH.transaction_id')
            ->leftJoin('Test_Methods as TM', 'Transaction_Detail.method_id', '=', 'TM.method_id')
            ->leftJoin('Internal_Users as IU', 'Transaction_Detail.internal_id', '=', 'IU.user_id')
            ->select('Transaction_Detail.*')
            ->selectRaw('TH.detail as job_detail')
            ->selectRaw('TM.method_name as joined_method_name')
            ->selectRaw('IU.name as joined_inspector_name')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($subQuery) use ($search) {
                    $applyLikeSearch = static function ($likeQuery) use ($search): void {
                        $likeQuery->where('Transaction_Detail.detail_id', 'like', "%{$search}%")
                            ->orWhere('Transaction_Detail.transaction_id', 'like', "%{$search}%")
                            ->orWhere('Transaction_Detail.remark', 'like', "%{$search}%")
                            ->orWhere('Transaction_Detail.max_value', 'like', "%{$search}%")
                            ->orWhere('Transaction_Detail.min_value', 'like', "%{$search}%")
                            ->orWhere('TM.method_name', 'like', "%{$search}%")
                            ->orWhere('IU.name', 'like', "%{$search}%")
                            ->orWhere('TH.detail', 'like', "%{$search}%")
                            ->orWhere('TH.dmc', 'like', "%{$search}%")
                            ->orWhere('TH.line', 'like', "%{$search}%");
                    };

                    if (SearchTerm::canUseFullText($search)) {
                        $term = SearchTerm::toBooleanTerm($search);
                        if ($term !== '') {
                            $subQuery
                                ->whereRaw("MATCH(Transaction_Detail.remark, Transaction_Detail.max_value, Transaction_Detail.min_value) AGAINST (? IN BOOLEAN MODE)", [$term])
                                ->orWhereRaw("MATCH(TM.method_name) AGAINST (? IN BOOLEAN MODE)", [$term])
                                ->orWhereRaw("MATCH(IU.name) AGAINST (? IN BOOLEAN MODE)", [$term])
                                ->orWhereRaw("MATCH(TH.detail, TH.dmc, TH.line) AGAINST (? IN BOOLEAN MODE)", [$term]);

                            $subQuery->orWhere(function ($likeQuery) use ($applyLikeSearch) {
                                $applyLikeSearch($likeQuery);
                            });

                            return;
                        }
                    }

                    $applyLikeSearch($subQuery);
                });
            })
            ->when(
                $filters['judgement'] !== 'all',
                fn ($query) => $query->where('judgement', $filters['judgement'])
            )
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('start_time', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('start_time', '<=', Carbon::parse($filters['date_to'])->endOfDay()))
            ->orderByDesc('Transaction_Detail.detail_id')
            ->simplePaginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (TransactionDetail $detail) => [
                'detail_id' => $detail->detail_id,
                'transaction_id' => $detail->transaction_id,
                'method_id' => $detail->method_id,
                'internal_id' => $detail->internal_id,
                'judgement' => $detail->judgement,
                'max_value' => $detail->max_value,
                'min_value' => $detail->min_value,
                'remark' => $detail->remark,
                'start_date' => optional($detail->start_time)->format('Y-m-d'),
                'start_time' => optional($detail->start_time)->format('H:i'),
                'end_date' => optional($detail->end_time)->format('Y-m-d'),
                'end_time' => optional($detail->end_time)->format('H:i'),
                'deleted_at' => $supportsDetailSoftDeletes ? optional($detail->deleted_at)->format('Y-m-d H:i') : null,
                'job_label' => '#' . $detail->transaction_id . ' - ' . ($detail->job_detail ?: 'No detail'),
                'method_name' => $detail->joined_method_name,
                'inspector_name' => $detail->joined_inspector_name,
                'is_deleted' => $supportsDetailSoftDeletes && $detail->trashed(),
            ]);
    }

    private function detailAuditPayload(TransactionDetail $detail): array
    {
        return [
            'detail_id' => (int) $detail->detail_id,
            'transaction_id' => (int) $detail->transaction_id,
            'method_id' => (int) $detail->method_id,
            'internal_id' => (int) $detail->internal_id,
            'start_time' => optional($detail->start_time)->format('Y-m-d H:i:s'),
            'end_time' => optional($detail->end_time)->format('Y-m-d H:i:s'),
            'duration_sec' => $detail->duration_sec === null ? null : (int) $detail->duration_sec,
            'max_value' => $detail->max_value,
            'min_value' => $detail->min_value,
            'judgement' => $detail->judgement,
            'remark' => $detail->remark,
            'deleted_at' => TransactionDetail::supportsSoftDeletes()
                ? optional($detail->deleted_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
