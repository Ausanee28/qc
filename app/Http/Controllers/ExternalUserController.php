<?php

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ExternalUserController extends Controller
{
    public function index()
    {
        $externalUsers = ExternalUser::with('department')->orderBy('external_name')->get();
        $departments = Department::orderBy('department_name')->get();
        
        return Inertia::render('MasterData/ExternalUsers/Index', [
            'externalUsers' => $externalUsers,
            'departments' => $departments
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
