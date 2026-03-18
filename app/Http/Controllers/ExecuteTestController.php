<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\TestMethod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'pendingJobs' => fn () => TransactionHeader::whereNull('return_date')
                ->orderByDesc('receive_date')
                ->get()
                ->map(fn ($job) => [
                    'transaction_id' => $job->transaction_id,
                    'dmc' => $job->dmc,
                    'line' => $job->line,
                    'detail' => $job->detail,
                ]),
            'pendingJobsVersion' => fn () => $this->pendingJobsVersionToken(),
            'methods' => fn () => TestMethod::orderBy('method_name')->get(),
            'inspectors' => fn () => User::orderBy('name')->get(['user_id', 'name']),
            'results' => fn () => TransactionDetail::query()
                ->when($supportsDetailSoftDeletes && $filters['record_state'] === 'all', fn ($query) => $query->withTrashed())
                ->when($supportsDetailSoftDeletes && $filters['record_state'] === 'deleted', fn ($query) => $query->onlyTrashed())
                ->with([
                    'transactionHeader:transaction_id,dmc,line,detail',
                    'testMethod:method_id,method_name',
                    'inspector:user_id,name',
                ])
                ->when($filters['search'] !== '', function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('detail_id', 'like', "%{$search}%")
                            ->orWhere('transaction_id', 'like', "%{$search}%")
                            ->orWhere('remark', 'like', "%{$search}%")
                            ->orWhereHas('testMethod', fn ($q) => $q->where('method_name', 'like', "%{$search}%"))
                            ->orWhereHas('inspector', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('transactionHeader', function ($q) use ($search) {
                                $q->where('detail', 'like', "%{$search}%")
                                    ->orWhere('dmc', 'like', "%{$search}%")
                                    ->orWhere('line', 'like', "%{$search}%");
                            });
                    });
                })
                ->when(
                    $filters['judgement'] !== 'all',
                    fn ($query) => $query->where('judgement', $filters['judgement'])
                )
                ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('start_time', '>=', $filters['date_from']))
                ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('start_time', '<=', $filters['date_to']))
                ->orderByDesc('detail_id')
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
                    'job_label' => '#' . $detail->transaction_id . ' - ' . ($detail->transactionHeader?->detail ?: 'No detail'),
                    'method_name' => $detail->testMethod?->method_name,
                    'inspector_name' => $detail->inspector?->name,
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
        $aggregate = TransactionHeader::query()
            ->whereNull('return_date')
            ->selectRaw('
                COUNT(*) as total,
                COALESCE(MAX(transaction_id), 0) as max_id,
                COALESCE(
                    SUM(
                        CRC32(
                            CONCAT_WS(
                                "|",
                                transaction_id,
                                COALESCE(dmc, ""),
                                COALESCE(line, ""),
                                COALESCE(detail, ""),
                                COALESCE(receive_date, "")
                            )
                        )
                    ),
                    0
                ) as checksum
            ')
            ->first();

        return implode(':', [
            (string) ($aggregate->total ?? 0),
            (string) ($aggregate->max_id ?? 0),
            (string) ($aggregate->checksum ?? 0),
        ]);
    }
}
