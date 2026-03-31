<?php

namespace App\Http\Controllers;

use App\Support\SchemaCapabilities;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 20);

        return Inertia::render('MasterData/Users/Index', [
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
            ],
            'users' => fn () => User::query()
                ->select(['user_id', 'user_name', 'name', 'employee_id', 'role'])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('user_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%")
                            ->orWhere('role', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate($perPage)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_name'   => 'required|string|max:50|unique:Internal_Users,user_name',
            'name'        => 'required|string|max:100',
            'employee_id' => 'nullable|string|max:50|unique:Internal_Users,employee_id',
            'role'        => 'required|in:admin,engineer,inspector',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'user_name'     => $request->user_name,
            'user_password' => Hash::make($request->password),
            'name'          => $request->name,
            'employee_id'   => $request->employee_id,
            'role'          => $request->role,
        ]);
        Cache::forget('receive_job.internals');
        Cache::forget('execute_test.inspectors');

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'user_name'   => 'required|string|max:50|unique:Internal_Users,user_name,' . $user->user_id . ',user_id',
            'name'        => 'required|string|max:100',
            'employee_id' => 'nullable|string|max:50|unique:Internal_Users,employee_id,' . $user->user_id . ',user_id',
            'role'        => 'required|in:admin,engineer,inspector',
            'password'    => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'user_name'   => $request->user_name,
            'name'        => $request->name,
            'employee_id' => $request->employee_id,
            'role'        => $request->role,
        ];

        if ($request->filled('password')) {
            $data['user_password'] = Hash::make($request->password);
        }

        $user->update($data);
        Cache::forget('receive_job.internals');
        Cache::forget('execute_test.inspectors');

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $hasDetailDeletedAt = SchemaCapabilities::hasColumn('Transaction_Detail', 'deleted_at');
        $hasHeaderDeletedAt = SchemaCapabilities::hasColumn('Transaction_Header', 'deleted_at');

        if (auth()->id() === $user->user_id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $hasTransactionsQuery = DB::table('Transaction_Detail')
            ->where('internal_id', $user->user_id);

        if ($hasDetailDeletedAt) {
            $hasTransactionsQuery->whereNull('deleted_at');
        }

        $hasTransactions = $hasTransactionsQuery->exists();

        $hasHeadersQuery = DB::table('Transaction_Header')
            ->where('internal_id', $user->user_id);

        if ($hasHeaderDeletedAt) {
            $hasHeadersQuery->whereNull('deleted_at');
        }

        $hasHeaders = $hasHeadersQuery->exists();

        if ($hasTransactions || $hasHeaders) {
            return redirect()->back()->with('error', 'Cannot delete user that has existing transactions.');
        }

        $user->delete();
        Cache::forget('receive_job.internals');
        Cache::forget('execute_test.inspectors');

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (blank($user->employee_id)) {
            return redirect()->back()->with('error', 'This user does not have an employee ID. Add one before using admin reset.');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ((string) $validated['employee_id'] !== (string) $user->employee_id) {
            return redirect()->back()->withErrors([
                'employee_id' => 'Employee ID does not match this user.',
            ]);
        }

        $user->update([
            'user_password' => Hash::make($validated['password']),
            'remember_token' => null,
        ]);

        return redirect()->back()->with('success', "Password reset successfully for {$user->name}.");
    }
}
