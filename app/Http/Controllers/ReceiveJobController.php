<?php

namespace App\Http\Controllers;

use App\Events\DashboardDataChanged;
use App\Models\TransactionHeader;
use App\Models\ExternalUser;
use App\Models\TransactionDetail;
use App\Support\PendingJobsVersion;
use App\Support\DashboardCache;
use App\Support\AuditLogger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReceiveJobController extends Controller
{
    private const EXECUTE_TEST_PENDING_JOBS_CACHE_KEY = 'execute_test.pending_jobs.active';
    private const EXECUTE_TEST_PENDING_JOBS_COUNT_CACHE_KEY = 'execute_test.pending_jobs_count.active';
    public const RECEIVE_JOB_DEFAULT_HISTORY_CACHE_KEY = 'receive_job.jobs.default.per_page_20';

    public function create(Request $request)
    {
        $supportsHeaderSoftDeletes = TransactionHeader::supportsSoftDeletes();

        $validatedFilters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:all,open,closed,deleted',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'per_page' => 'nullable|integer|in:10,20,50,100',
        ]);

        $filters = [
            'search' => trim((string) ($validatedFilters['search'] ?? '')),
            'status' => (string) ($validatedFilters['status'] ?? 'all'),
            'date_from' => (string) ($validatedFilters['date_from'] ?? ''),
            'date_to' => (string) ($validatedFilters['date_to'] ?? ''),
            'per_page' => (int) ($validatedFilters['per_page'] ?? 20),
        ];
        $currentPage = max(1, (int) $request->integer('page', 1));

        if (!$supportsHeaderSoftDeletes && $filters['status'] === 'deleted') {
            $filters['status'] = 'all';
        }

        $jobsQuery = TransactionHeader::query()
            ->leftJoin('External_Users as EU', 'Transaction_Header.external_id', '=', 'EU.external_id')
            ->leftJoin('Internal_Users as IU', 'Transaction_Header.internal_id', '=', 'IU.user_id')
            ->select('Transaction_Header.*')
            ->selectRaw('EU.external_name as external_name')
            ->selectRaw('IU.name as internal_name')
            ->withCount('details')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('detail', 'like', "%{$search}%")
                        ->orWhere('dmc', 'like', "%{$search}%")
                        ->orWhere('line', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhere('EU.external_name', 'like', "%{$search}%")
                        ->orWhere('IU.name', 'like', "%{$search}%");
                });
            })
            ->when($supportsHeaderSoftDeletes && $filters['status'] === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($filters['status'] === 'open', fn ($query) => $query->whereNull('return_date'))
            ->when($filters['status'] === 'closed', fn ($query) => $query->whereNotNull('return_date'))
            ->when($filters['date_from'] !== '', fn ($query) => $query->where('receive_date', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
            ->when($filters['date_to'] !== '', fn ($query) => $query->where('receive_date', '<=', Carbon::parse($filters['date_to'])->endOfDay()));

        return Inertia::render('ReceiveJob/Create', [
            'externals' => fn () => Cache::remember('receive_job.externals', now()->addMinutes(10), function () {
                return ExternalUser::orderBy('external_name')
                    ->get(['external_id', 'external_name']);
            }),
            'internals' => fn () => Cache::remember('receive_job.internals', now()->addMinutes(10), function () {
                return User::orderBy('name')
                    ->get(['user_id', 'name']);
            }),
            'jobs' => fn () => $this->resolveJobsPayload($jobsQuery, $filters, $supportsHeaderSoftDeletes, $currentPage),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $job = TransactionHeader::create([
            ...$validated,
            'receive_date' => now(),
            'return_date' => null,
        ]);
        AuditLogger::log(
            'receive_job',
            'create',
            'Transaction_Header',
            $job->transaction_id,
            null,
            $this->headerAuditPayload($job)
        );
        $this->forgetReceiveJobHistoryCaches();
        $this->forgetExecuteTestPendingJobsCaches();
        PendingJobsVersion::bump();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} created successfully!");
    }

    public function update(Request $request, int $id)
    {
        $job = TransactionHeader::withCount('details')->findOrFail($id);
        $beforeData = $this->headerAuditPayload($job);

        if ($job->details_count > 0 && $job->return_date === null) {
            return redirect()->back()->with('error', 'Cannot edit a job after test results have been recorded.');
        }

        $validated = $this->validatePayload($request);
        $job->update($validated);
        $job->refresh();
        AuditLogger::log(
            'receive_job',
            'update',
            'Transaction_Header',
            $job->transaction_id,
            $beforeData,
            $this->headerAuditPayload($job)
        );
        $this->forgetReceiveJobHistoryCaches();
        $this->forgetExecuteTestPendingJobsCaches();
        PendingJobsVersion::bump();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} updated successfully!");
    }

    public function destroy(int $id)
    {
        if (auth()->user()?->role !== 'admin') {
            return response('Forbidden.', 403);
        }

        $job = TransactionHeader::withCount('details')->findOrFail($id);
        $beforeData = $this->headerAuditPayload($job);

        if ($job->details_count > 0 && $job->return_date === null) {
            return redirect()->back()->with('error', 'Cannot delete a job that already has test results.');
        }

        DB::transaction(function () use ($job) {
            if ($job->details_count > 0) {
                TransactionDetail::where('transaction_id', $job->transaction_id)->delete();
            }

            $job->delete();
        });
        $afterData = null;
        if (TransactionHeader::supportsSoftDeletes()) {
            $deletedJob = TransactionHeader::withTrashed()->find($job->transaction_id);
            $afterData = $deletedJob ? $this->headerAuditPayload($deletedJob) : null;
        }

        AuditLogger::log(
            'receive_job',
            'delete',
            'Transaction_Header',
            $id,
            $beforeData,
            $afterData
        );
        $this->forgetReceiveJobHistoryCaches();
        $this->forgetExecuteTestPendingJobsCaches();
        PendingJobsVersion::bump();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$id} deleted successfully!");
    }

    public function restore(int $id)
    {
        if (auth()->user()?->role !== 'admin') {
            return response('Forbidden.', 403);
        }

        if (!TransactionHeader::supportsSoftDeletes()) {
            return redirect()->route('receive-job.create')
                ->with('error', 'Restore is unavailable because this database does not support soft deletes.');
        }

        $job = TransactionHeader::onlyTrashed()->findOrFail($id);
        $beforeData = $this->headerAuditPayload($job);

        DB::transaction(function () use ($job) {
            $job->restore();
            if (TransactionDetail::supportsSoftDeletes()) {
                TransactionDetail::onlyTrashed()
                    ->where('transaction_id', $job->transaction_id)
                    ->restore();
            }
        });

        $restoredJob = TransactionHeader::withTrashed()->find($job->transaction_id);
        AuditLogger::log(
            'receive_job',
            'restore',
            'Transaction_Header',
            $job->transaction_id,
            $beforeData,
            $restoredJob ? $this->headerAuditPayload($restoredJob) : null
        );
        $this->forgetReceiveJobHistoryCaches();
        $this->forgetExecuteTestPendingJobsCaches();
        PendingJobsVersion::bump();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} restored successfully!");
    }

    public function close(int $id)
    {
        $job = TransactionHeader::findOrFail($id);

        if (!TransactionDetail::where('transaction_id', $job->transaction_id)->exists()) {
            return redirect()->back()->with('error', 'Record at least one test result before closing the job.');
        }

        $job->update([
            'return_date' => now(),
        ]);
        $this->forgetReceiveJobHistoryCaches();
        $this->forgetExecuteTestPendingJobsCaches();
        PendingJobsVersion::bump();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} closed successfully!");
    }

    public function reopen(int $id)
    {
        $job = TransactionHeader::findOrFail($id);

        $job->update([
            'return_date' => null,
        ]);
        $this->forgetReceiveJobHistoryCaches();
        $this->forgetExecuteTestPendingJobsCaches();
        PendingJobsVersion::bump();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} reopened successfully!");
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'external_id' => 'required|exists:External_Users,external_id',
            'internal_id' => 'required|exists:Internal_Users,user_id',
            'detail' => 'nullable|string|max:255',
            'dmc' => 'nullable|string',
            'line' => 'nullable|string',
        ]);
    }

    private function forgetExecuteTestPendingJobsCaches(): void
    {
        Cache::forget(self::EXECUTE_TEST_PENDING_JOBS_CACHE_KEY);
        Cache::forget(self::EXECUTE_TEST_PENDING_JOBS_COUNT_CACHE_KEY);
    }

    private function forgetReceiveJobHistoryCaches(): void
    {
        Cache::forget(self::RECEIVE_JOB_DEFAULT_HISTORY_CACHE_KEY);
    }

    public static function warmDefaultHistoryCache(): void
    {
        $supportsHeaderSoftDeletes = TransactionHeader::supportsSoftDeletes();
        $filters = [
            'search' => '',
            'status' => 'all',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
        ];

        $jobsQuery = TransactionHeader::query()
            ->leftJoin('External_Users as EU', 'Transaction_Header.external_id', '=', 'EU.external_id')
            ->leftJoin('Internal_Users as IU', 'Transaction_Header.internal_id', '=', 'IU.user_id')
            ->select('Transaction_Header.*')
            ->selectRaw('EU.external_name as external_name')
            ->selectRaw('IU.name as internal_name')
            ->withCount('details')
            ->when($filters['status'] === 'open', fn ($query) => $query->whereNull('return_date'))
            ->when($filters['status'] === 'closed', fn ($query) => $query->whereNotNull('return_date'))
            ->when($supportsHeaderSoftDeletes && $filters['status'] === 'deleted', fn ($query) => $query->onlyTrashed());

        Cache::remember(
            self::RECEIVE_JOB_DEFAULT_HISTORY_CACHE_KEY,
            now()->addSeconds(30),
            fn () => (new self())->buildJobsPayload($jobsQuery, $filters, $supportsHeaderSoftDeletes)
        );
    }

    private function resolveJobsPayload($jobsQuery, array $filters, bool $supportsHeaderSoftDeletes, int $currentPage)
    {
        if ($this->shouldCacheDefaultJobsPayload($filters, $currentPage)) {
            return Cache::remember(
                self::RECEIVE_JOB_DEFAULT_HISTORY_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildJobsPayload($jobsQuery, $filters, $supportsHeaderSoftDeletes)
            );
        }

        return $this->buildJobsPayload($jobsQuery, $filters, $supportsHeaderSoftDeletes);
    }

    private function shouldCacheDefaultJobsPayload(array $filters, int $currentPage): bool
    {
        return $currentPage === 1
            && $filters['search'] === ''
            && $filters['status'] === 'all'
            && $filters['date_from'] === ''
            && $filters['date_to'] === ''
            && $filters['per_page'] === 20;
    }

    private function buildJobsPayload($jobsQuery, array $filters, bool $supportsHeaderSoftDeletes)
    {
        return (clone $jobsQuery)
            ->orderByDesc('Transaction_Header.receive_date')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (TransactionHeader $job) => [
                'transaction_id' => $job->transaction_id,
                'external_id' => $job->external_id,
                'internal_id' => $job->internal_id,
                'detail' => $job->detail,
                'dmc' => $job->dmc,
                'line' => $job->line,
                'receive_date' => optional($job->receive_date)->format('Y-m-d H:i'),
                'return_date' => optional($job->return_date)->format('Y-m-d H:i'),
                'deleted_at' => $supportsHeaderSoftDeletes ? optional($job->deleted_at)->format('Y-m-d H:i') : null,
                'details_count' => $job->details_count,
                'external_name' => $job->external_name,
                'internal_name' => $job->internal_name,
                'is_closed' => $job->return_date !== null,
                'is_deleted' => $supportsHeaderSoftDeletes && $job->trashed(),
            ]);
    }

    private function headerAuditPayload(TransactionHeader $job): array
    {
        return [
            'transaction_id' => (int) $job->transaction_id,
            'external_id' => (int) $job->external_id,
            'internal_id' => (int) $job->internal_id,
            'detail' => $job->detail,
            'dmc' => $job->dmc,
            'line' => $job->line,
            'receive_date' => optional($job->receive_date)->format('Y-m-d H:i:s'),
            'return_date' => optional($job->return_date)->format('Y-m-d H:i:s'),
            'deleted_at' => TransactionHeader::supportsSoftDeletes()
                ? optional($job->deleted_at)->format('Y-m-d H:i:s')
                : null,
            'details_count' => isset($job->details_count) ? (int) $job->details_count : null,
        ];
    }
}
