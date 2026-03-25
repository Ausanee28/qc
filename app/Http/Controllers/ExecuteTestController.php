<?php

namespace App\Http\Controllers;

use App\Events\DashboardDataChanged;
use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\TestMethod;
use App\Models\User;
use App\Support\PendingJobsVersion;
use App\Support\DashboardCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExecuteTestController extends Controller
{
    public function create(Request $request)
    {
        $supportsDetailSoftDeletes = TransactionDetail::supportsSoftDeletes();

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
            'pendingJobs' => Inertia::defer(
                fn () => TransactionHeader::whereNull('return_date')
                    ->orderByDesc('receive_date')
                    ->get(['transaction_id', 'dmc', 'line', 'detail'])
                    ->map(fn ($job) => [
                        'transaction_id' => $job->transaction_id,
                        'dmc' => $job->dmc,
                        'line' => $job->line,
                        'detail' => $job->detail,
                    ]),
                'workflow-options'
            ),
            'pendingJobsCount' => fn () => TransactionHeader::whereNull('return_date')->count(),
            'pendingJobsVersion' => fn () => $this->pendingJobsVersionToken(),
            'methods' => Inertia::defer(
                fn () => Cache::remember('execute_test.methods', now()->addMinutes(10), fn () => TestMethod::orderBy('method_name')->get()),
                'workflow-options'
            ),
            'inspectors' => Inertia::defer(
                fn () => Cache::remember('execute_test.inspectors', now()->addMinutes(10), fn () => User::orderBy('name')->get(['user_id', 'name'])),
                'workflow-options'
            ),
            'results' => fn () => TransactionDetail::query()
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
                        $subQuery->where('detail_id', 'like', "%{$search}%")
                            ->orWhere('transaction_id', 'like', "%{$search}%")
                            ->orWhere('remark', 'like', "%{$search}%")
                            ->orWhere('TM.method_name', 'like', "%{$search}%")
                            ->orWhere('IU.name', 'like', "%{$search}%")
                            ->orWhere('TH.detail', 'like', "%{$search}%")
                            ->orWhere('TH.dmc', 'like', "%{$search}%")
                            ->orWhere('TH.line', 'like', "%{$search}%");
                    });
                })
                ->when(
                    $filters['judgement'] !== 'all',
                    fn ($query) => $query->where('judgement', $filters['judgement'])
                )
                ->when($filters['date_from'] !== '', fn ($query) => $query->where('start_time', '>=', Carbon::parse($filters['date_from'])->startOfDay()))
                ->when($filters['date_to'] !== '', fn ($query) => $query->where('start_time', '<=', Carbon::parse($filters['date_to'])->endOfDay()))
                ->orderByDesc('Transaction_Detail.detail_id')
                ->paginate($filters['per_page'])
                ->withQueryString()
                ->through(fn (TransactionDetail $detail) => [
                    'detail_id' => $detail->detail_id,
                    'transaction_id' => $detail->transaction_id,
                    'method_id' => $detail->method_id,
                    'internal_id' => $detail->internal_id,
                    'judgement' => $detail->judgement,
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
                ]),
            'filters' => $filters,
        ]);
    }

    public function pendingJobsVersion(): JsonResponse
    {
        return response()->json([
            'version' => $this->pendingJobsVersionToken(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        [$startDt, $endDt, $durationSec] = $this->normalizeTimes($validated);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $startDt, $endDt, $durationSec) {
            TransactionDetail::create([
                'transaction_id' => $validated['transaction_id'],
                'method_id' => $validated['method_id'],
                'internal_id' => $validated['internal_id'],
                'start_time' => $startDt,
                'end_time' => $endDt,
                'duration_sec' => $durationSec,
                'judgement' => $validated['judgement'],
                'remark' => $validated['remark'] ?? null,
            ]);
        });
        $this->forgetPerformanceCaches();
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();

        return redirect()->route('execute-test.create')
            ->with('success', "Test result recorded for Job #{$validated['transaction_id']}!");
    }

    public function update(Request $request, int $id)
    {
        $detail = TransactionDetail::findOrFail($id);
        $validated = $this->validatePayload($request);
        [$startDt, $endDt, $durationSec] = $this->normalizeTimes($validated);

        $detail->update([
            'transaction_id' => $validated['transaction_id'],
            'method_id' => $validated['method_id'],
            'internal_id' => $validated['internal_id'],
            'start_time' => $startDt,
            'end_time' => $endDt,
            'duration_sec' => $durationSec,
            'judgement' => $validated['judgement'],
            'remark' => $validated['remark'] ?? null,
        ]);
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
        $detail->delete();
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
        $detail->restore();
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
            'remark' => 'nullable|string|max:255',
        ]);

        $job = TransactionHeader::find($validated['transaction_id']);

        if (!$job || $job->return_date !== null) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Selected job is not open for test execution.',
            ]);
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

    private function pendingJobsVersionToken(): string
    {
        return PendingJobsVersion::current();
    }

    private function forgetPerformanceCaches(): void
    {
        Cache::forget('performance.inspectors.30d');
        Cache::forget('performance.details.30d.recent50');
    }
}
