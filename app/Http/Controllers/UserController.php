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
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $perPage = (int) ($filters['per_page'] ?? 20);
        $hasIsActive = SchemaCapabilities::hasColumn('Internal_Users', 'is_active');

        $usersQuery = User::query()
            ->select(['user_id', 'user_name', 'name', 'employee_id', 'role'])
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
                $query->where(function ($userQuery) use ($search) {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('user_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        return Inertia::render('MasterData/Users/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => (string) $perPage,
            ],
            'users' => fn () => $usersQuery
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
            'role'        => 'required|in:admin,inspector',
            'is_active'   => 'nullable|boolean',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $payload = [
            'user_name'     => $request->user_name,
            'user_password' => Hash::make($request->password),
            'name'          => $request->name,
            'employee_id'   => $request->employee_id,
            'role'          => $request->role,
        ];

        if (SchemaCapabilities::hasColumn('Internal_Users', 'is_active')) {
            $payload['is_active'] = $request->boolean('is_active', true);
        }

        User::create($payload);
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
            'role'        => 'required|in:admin,inspector',
            'is_active'   => 'nullable|boolean',
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

        if (SchemaCapabilities::hasColumn('Internal_Users', 'is_active')) {
            $nextIsActive = $request->boolean('is_active', true);

            if (auth()->id() === $user->user_id && !$nextIsActive) {
                return redirect()->back()->with('error', 'You cannot deactivate your own account.');
            }

            $data['is_active'] = $nextIsActive;
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

    public function setActive(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $hasIsActive = SchemaCapabilities::hasColumn('Internal_Users', 'is_active');
        $hasRememberToken = SchemaCapabilities::hasColumn('Internal_Users', 'remember_token');

        if (!$hasIsActive) {
            return redirect()->back()->with('error', 'Inactive status is unavailable on this database.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        if (auth()->id() === $user->user_id && !$isActive) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $updatePayload = [
            'is_active' => $isActive,
        ];

        if ($hasRememberToken && !$isActive) {
            $updatePayload['remember_token'] = null;
        }

        $user->update($updatePayload);

        Cache::forget('receive_job.internals');
        Cache::forget('execute_test.inspectors');

        return redirect()->back()->with('success', $isActive
            ? 'User activated successfully.'
            : 'User deactivated successfully.');
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $hasRememberToken = SchemaCapabilities::hasColumn('Internal_Users', 'remember_token');

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

        $updatePayload = [
            'user_password' => Hash::make($validated['password']),
        ];

        if ($hasRememberToken) {
            $updatePayload['remember_token'] = null;
        }

        $user->update($updatePayload);

        return redirect()->back()->with('success', "Password reset successfully for {$user->name}.");
    }
}
