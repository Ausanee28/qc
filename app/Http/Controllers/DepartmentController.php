<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    private const DEFAULT_DEPARTMENTS_CACHE_KEY = 'master_data.departments.default.status_all.per_page_20';

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $perPage = (int) ($filters['per_page'] ?? 20);
        $currentPage = max(1, (int) $request->integer('page', 1));
        $hasIsActive = SchemaCapabilities::hasColumn('Departments', 'is_active');

        if (!$hasIsActive) {
            $status = 'all';
        }

        return Inertia::render('MasterData/Departments/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => (string) $perPage,
            ],
            'departments' => fn () => $this->resolveDepartmentsPayload($search, $status, $perPage, $currentPage, $hasIsActive),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => 'required|string|max:255|unique:Departments,department_name',
            'internal_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'department_name' => $validated['department_name'],
            'internal_phone' => $validated['internal_phone'] ?? null,
        ];

        if (SchemaCapabilities::hasColumn('Departments', 'is_active')) {
            $payload['is_active'] = (bool) ($validated['is_active'] ?? true);
        }

        Department::create($payload);
        $this->clearDepartmentCaches();

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'department_name' => 'required|string|max:255|unique:Departments,department_name,' . $department->department_id . ',department_id',
            'internal_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'department_name' => $validated['department_name'],
            'internal_phone' => $validated['internal_phone'] ?? null,
        ];

        if (SchemaCapabilities::hasColumn('Departments', 'is_active') && array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        }

        $department->update($payload);
        $this->clearDepartmentCaches();

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    public function setActive(Request $request, $id)
    {
        if (!SchemaCapabilities::hasColumn('Departments', 'is_active')) {
            return redirect()->back()->with('error', 'Status toggle is unavailable on this database.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $department = Department::findOrFail($id);
        $isActive = (bool) $validated['is_active'];
        $department->update(['is_active' => $isActive]);
        $this->clearDepartmentCaches();

        return redirect()->back()->with('success', $isActive
            ? 'Department activated successfully.'
            : 'Department deactivated successfully.');
    }

    public function destroy($id)
    {
        if (SchemaCapabilities::hasColumn('Departments', 'is_active')) {
            return redirect()->back()->with('error', 'Delete is disabled. Please change status instead.');
        }

        $department = Department::findOrFail($id);

        if ($department->externalUsers()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete department with assigned external users.');
        }

        $department->delete();
        $this->clearDepartmentCaches();

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }

    private function resolveDepartmentsPayload(string $search, string $status, int $perPage, int $currentPage, bool $hasIsActive)
    {
        if ($this->shouldCacheDefaultDepartmentsPayload($search, $status, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_DEPARTMENTS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildDepartmentsPayload($search, $status, $perPage, $hasIsActive)
            );
        }

        return $this->buildDepartmentsPayload($search, $status, $perPage, $hasIsActive);
    }

    private function shouldCacheDefaultDepartmentsPayload(string $search, string $status, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $status === 'all' && $perPage === 20;
    }

    private function buildDepartmentsPayload(string $search, string $status, int $perPage, bool $hasIsActive)
    {
        return Department::query()
            ->select(['department_id', 'department_name', 'internal_phone'])
            ->when($hasIsActive, function ($query) use ($status) {
                $query->addSelect('is_active');

                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when(!$hasIsActive, fn ($query) => $query->selectRaw('1 as is_active'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($departmentQuery) use ($search) {
                    $departmentQuery
                        ->where('department_name', 'like', "%{$search}%")
                        ->orWhere('internal_phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('department_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function clearDepartmentCaches(): void
    {
        Cache::forget(self::DEFAULT_DEPARTMENTS_CACHE_KEY);
        Cache::forget('master_data.external_users.default.status_all.per_page_20');
        Cache::forget('receive_job.externals');
    }
}
