<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ExternalUserController extends Controller
{
    private const DEFAULT_EXTERNAL_USERS_CACHE_KEY = 'master_data.external_users.default.per_page_20';

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 20);
        $currentPage = max(1, (int) $request->integer('page', 1));

        return Inertia::render('MasterData/ExternalUsers/Index', [
            'departments' => fn () => Department::query()->select(['department_id', 'department_name'])->orderBy('department_name')->get(),
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
            ],
            'externalUsers' => fn () => $this->resolveExternalUsersPayload($search, $perPage, $currentPage),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'external_name' => 'required|string|max:255',
            'department_id' => 'required|exists:Departments,department_id',
        ]);

        ExternalUser::create($validated);
        Cache::forget('receive_job.externals');
        Cache::forget(self::DEFAULT_EXTERNAL_USERS_CACHE_KEY);

        return redirect()->back()->with('success', 'External user created successfully.');
    }

    public function update(Request $request, $id)
    {
        $externalUser = ExternalUser::findOrFail($id);

        $validated = $request->validate([
            'external_name' => 'required|string|max:255',
            'department_id' => 'required|exists:Departments,department_id',
        ]);

        $externalUser->update($validated);
        Cache::forget('receive_job.externals');
        Cache::forget(self::DEFAULT_EXTERNAL_USERS_CACHE_KEY);

        return redirect()->back()->with('success', 'External user updated successfully.');
    }

    public function destroy($id)
    {
        $externalUser = ExternalUser::findOrFail($id);
        
        try {
            $externalUser->delete();
            Cache::forget('receive_job.externals');
            Cache::forget(self::DEFAULT_EXTERNAL_USERS_CACHE_KEY);
            return redirect()->back()->with('success', 'External user deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Cannot delete this external user as it may be referenced in transactions.');
        }
    }

    private function resolveExternalUsersPayload(string $search, int $perPage, int $currentPage)
    {
        if ($this->shouldCacheDefaultExternalUsersPayload($search, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_EXTERNAL_USERS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildExternalUsersPayload($search, $perPage)
            );
        }

        return $this->buildExternalUsersPayload($search, $perPage);
    }

    private function shouldCacheDefaultExternalUsersPayload(string $search, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $perPage === 20;
    }

    private function buildExternalUsersPayload(string $search, int $perPage)
    {
        return ExternalUser::query()
            ->select(['external_id', 'external_name', 'department_id'])
            ->with(['department:department_id,department_name'])
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
}
