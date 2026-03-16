<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get(['user_id', 'user_name', 'name', 'employee_id', 'email', 'role']);
        return Inertia::render('MasterData/Users/Index', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_name'   => 'required|string|max:50|unique:Internal_Users,user_name',
            'name'        => 'required|string|max:100',
            'employee_id' => 'nullable|string|max:50|unique:Internal_Users,employee_id',
            'email'       => 'nullable|email|max:255|unique:Internal_Users,email',
            'role'        => 'required|in:admin,engineer,inspector',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'user_name'     => $request->user_name,
            'user_password' => Hash::make($request->password),
            'name'          => $request->name,
            'employee_id'   => $request->employee_id,
            'email'         => $request->email,
            'role'          => $request->role,
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'user_name'   => 'required|string|max:50|unique:Internal_Users,user_name,' . $user->user_id . ',user_id',
            'name'        => 'required|string|max:100',
            'employee_id' => 'nullable|string|max:50|unique:Internal_Users,employee_id,' . $user->user_id . ',user_id',
            'email'       => 'nullable|email|max:255|unique:Internal_Users,email,' . $user->user_id . ',user_id',
            'role'        => 'required|in:admin,engineer,inspector',
            'password'    => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'user_name'   => $request->user_name,
            'name'        => $request->name,
            'employee_id' => $request->employee_id,
            'email'       => $request->email,
            'role'        => $request->role,
        ];

        if ($request->filled('password')) {
            $data['user_password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() === $user->user_id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $hasTransactions = DB::table('Transaction_Detail')
            ->where('internal_id', $user->user_id)->exists();

        $hasHeaders = DB::table('Transaction_Header')
            ->where('internal_id', $user->user_id)->exists();

        if ($hasTransactions || $hasHeaders) {
            return redirect()->back()->with('error', 'Cannot delete user that has existing transactions.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
