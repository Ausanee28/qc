<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ExternalUserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 20);

        return Inertia::render('MasterData/ExternalUsers/Index', [
            'departments' => fn () => Department::query()->select(['department_id', 'department_name'])->orderBy('department_name')->get(),
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
            ],
            'externalUsers' => fn () => ExternalUser::query()
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
                ->withQueryString(),
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

        return redirect()->back()->with('success', 'External user updated successfully.');
    }

    public function destroy($id)
    {
        $externalUser = ExternalUser::findOrFail($id);
        
        try {
            $externalUser->delete();
            Cache::forget('receive_job.externals');
            return redirect()->back()->with('success', 'External user deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Cannot delete this external user as it may be referenced in transactions.');
        }
    }
}
