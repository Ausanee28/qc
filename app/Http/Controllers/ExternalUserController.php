<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use App\Models\Department;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ExternalUserController extends Controller
{
    private const DEFAULT_EXTERNAL_USERS_CACHE_KEY = 'master_data.external_users.default.status_all.per_page_20';

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
        $hasIsActive = SchemaCapabilities::hasColumn('External_Users', 'is_active');

        if (!$hasIsActive) {
            $status = 'all';
        }

        return Inertia::render('MasterData/ExternalUsers/Index', [
            'departments' => fn () => Department::query()
                ->select(['department_id', 'department_name'])
                ->orderBy('department_name')
                ->get(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => (string) $perPage,
            ],
            'externalUsers' => fn () => $this->resolveExternalUsersPayload($search, $status, $perPage, $currentPage, $hasIsActive),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'external_name' => 'required|string|max:255',
            'department_id' => 'required|exists:Departments,department_id',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'external_name' => $validated['external_name'],
            'department_id' => $validated['department_id'],
        ];

        if (SchemaCapabilities::hasColumn('External_Users', 'is_active')) {
            $payload['is_active'] = (bool) ($validated['is_active'] ?? true);
        }

        ExternalUser::create($payload);
        $this->clearExternalUserCaches();

        return redirect()->back()->with('success', 'External user created successfully.');
    }

    public function update(Request $request, $id)
    {
        $externalUser = ExternalUser::findOrFail($id);

        $validated = $request->validate([
            'external_name' => 'required|string|max:255',
            'department_id' => 'required|exists:Departments,department_id',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'external_name' => $validated['external_name'],
            'department_id' => $validated['department_id'],
        ];

        if (SchemaCapabilities::hasColumn('External_Users', 'is_active') && array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        }

        $externalUser->update($payload);
        $this->clearExternalUserCaches();

        return redirect()->back()->with('success', 'External user updated successfully.');
    }

    public function setActive(Request $request, $id)
    {
        if (!SchemaCapabilities::hasColumn('External_Users', 'is_active')) {
            return redirect()->back()->with('error', 'Status toggle is unavailable on this database.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $externalUser = ExternalUser::findOrFail($id);
        $isActive = (bool) $validated['is_active'];
        $externalUser->update(['is_active' => $isActive]);
        $this->clearExternalUserCaches();

        return redirect()->back()->with('success', $isActive
            ? 'External user activated successfully.'
            : 'External user deactivated successfully.');
    }

    public function destroy($id)
    {
        if (SchemaCapabilities::hasColumn('External_Users', 'is_active')) {
            return redirect()->back()->with('error', 'Delete is disabled. Please change status instead.');
        }

        $externalUser = ExternalUser::findOrFail($id);
        
        try {
            $externalUser->delete();
            $this->clearExternalUserCaches();
            return redirect()->back()->with('success', 'External user deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Cannot delete this external user as it may be referenced in transactions.');
        }
    }

    private function resolveExternalUsersPayload(string $search, string $status, int $perPage, int $currentPage, bool $hasIsActive)
    {
        if ($this->shouldCacheDefaultExternalUsersPayload($search, $status, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_EXTERNAL_USERS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildExternalUsersPayload($search, $status, $perPage, $hasIsActive)
            );
        }

        return $this->buildExternalUsersPayload($search, $status, $perPage, $hasIsActive);
    }

    private function shouldCacheDefaultExternalUsersPayload(string $search, string $status, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $status === 'all' && $perPage === 20;
    }

    private function buildExternalUsersPayload(string $search, string $status, int $perPage, bool $hasIsActive)
    {
        return ExternalUser::query()
            ->select(['external_id', 'external_name', 'department_id'])
            ->with(['department:department_id,department_name'])
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
                $query->where(function ($externalUserQuery) use ($search) {
                    $externalUserQuery
                        ->where('external_name', 'like', "%{$search}%")
                        ->orWhereHas('department', function ($departmentQuery) use ($search) {
                            $departmentQuery->where('department_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('external_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function clearExternalUserCaches(): void
    {
        Cache::forget('receive_job.externals');
        Cache::forget(self::DEFAULT_EXTERNAL_USERS_CACHE_KEY);
    }
}
