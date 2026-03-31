<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 20);

        return Inertia::render('MasterData/Departments/Index', [
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
            ],
            'departments' => fn () => Department::query()
                ->select(['department_id', 'department_name', 'internal_phone'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($departmentQuery) use ($search) {
                        $departmentQuery
                            ->where('department_name', 'like', "%{$search}%")
                            ->orWhere('internal_phone', 'like', "%{$search}%");
                    });
                })
                ->orderBy('department_name')
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => 'required|string|max:255|unique:Departments,department_name',
            'internal_phone' => 'nullable|string|max:50',
        ]);

        Department::create($validated);

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'department_name' => 'required|string|max:255|unique:Departments,department_name,' . $department->department_id . ',department_id',
            'internal_phone' => 'nullable|string|max:50',
        ]);

        $department->update($validated);

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        
        // We might want to check for related external users first, but for simplicity we'll just delete or let DB cascade/restrict
        // Better to check if related models exist
        if ($department->externalUsers()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete department with assigned external users.');
        }

        $department->delete();

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }
}
