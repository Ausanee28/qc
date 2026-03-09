<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('department_name')->get();
        return Inertia::render('MasterData/Departments/Index', [
            'departments' => $departments
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
