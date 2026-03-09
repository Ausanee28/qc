<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return Inertia::render('MasterData/Users/Index', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255|unique:Internal_Users,user_name',
            'employee_id' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'role' => ['required', Rule::in(['admin', 'qc'])],
            'password' => 'required|string|min:8',
        ]);

        $user = new User();
        $user->user_name = $validated['user_name'];
        $user->employee_id = $validated['employee_id'];
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->user_password = Hash::make($validated['password']);
        $user->save();

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'user_name' => 'required|string|max:255|unique:Internal_Users,user_name,' . $user->user_id . ',user_id',
            'employee_id' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'role' => ['required', Rule::in(['admin', 'qc'])],
            'password' => 'nullable|string|min:8',
        ]);

        $user->user_name = $validated['user_name'];
        $user->employee_id = $validated['employee_id'];
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        
        if (!empty($validated['password'])) {
            $user->user_password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion if needed (assuming auth check is here)
        if (auth()->id() == $user->user_id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
