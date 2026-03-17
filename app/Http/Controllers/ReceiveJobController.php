<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\ExternalUser;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReceiveJobController extends Controller
{
    public function create(Request $request)
    {
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

        $jobsQuery = TransactionHeader::with(['externalUser:external_id,external_name', 'internalUser:user_id,name'])
            ->withCount('details')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('detail', 'like', "%{$search}%")
                        ->orWhere('dmc', 'like', "%{$search}%")
                        ->orWhere('line', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('externalUser', fn ($q) => $q->where('external_name', 'like', "%{$search}%"))
                        ->orWhereHas('internalUser', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($filters['status'] === 'open', fn ($query) => $query->whereNull('return_date'))
            ->when($filters['status'] === 'closed', fn ($query) => $query->whereNotNull('return_date'))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('receive_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('receive_date', '<=', $filters['date_to']));

        return Inertia::render('ReceiveJob/Create', [
            'externals' => ExternalUser::orderBy('external_name')->get(),
            'internals' => User::orderBy('name')->get(['user_id', 'name']),
            'jobs' => $jobsQuery
                ->orderByDesc('receive_date')
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
                    'deleted_at' => optional($job->deleted_at)->format('Y-m-d H:i'),
                    'details_count' => $job->details_count,
                    'external_name' => $job->externalUser?->external_name,
                    'internal_name' => $job->internalUser?->name,
                    'is_closed' => $job->return_date !== null,
                    'is_deleted' => $job->trashed(),
                ]),
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

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} created successfully!");
    }

    public function update(Request $request, int $id)
    {
        $job = TransactionHeader::withCount('details')->findOrFail($id);

        if ($job->details_count > 0 && $job->return_date === null) {
            return redirect()->back()->with('error', 'Cannot edit a job after test results have been recorded.');
        }

        $validated = $this->validatePayload($request);
        $job->update($validated);

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} updated successfully!");
    }

    public function destroy(int $id)
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403, 'Only admins can delete jobs.');
        }

        $job = TransactionHeader::withCount('details')->findOrFail($id);

        if ($job->details_count > 0 && $job->return_date === null) {
            return redirect()->back()->with('error', 'Cannot delete a job that already has test results.');
        }

        DB::transaction(function () use ($job) {
            if ($job->details_count > 0) {
                TransactionDetail::where('transaction_id', $job->transaction_id)->delete();
            }

            $job->delete();
        });

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$id} deleted successfully!");
    }

    public function restore(int $id)
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403, 'Only admins can restore jobs.');
        }

        $job = TransactionHeader::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($job) {
            $job->restore();
            TransactionDetail::onlyTrashed()
                ->where('transaction_id', $job->transaction_id)
                ->restore();
        });

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

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} closed successfully!");
    }

    public function reopen(int $id)
    {
        $job = TransactionHeader::findOrFail($id);

        $job->update([
            'return_date' => null,
        ]);

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
}
