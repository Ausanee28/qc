<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_name' => 'required|string|max:50|unique:Internal_Users,user_name',
            'name' => 'required|string|max:255',
            'employee_id' => 'required|string|max:50|unique:Internal_Users,employee_id',
            'email' => 'required|string|lowercase|email|max:255|unique:Internal_Users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'user_name' => $request->user_name,
            'user_password' => Hash::make($request->password),
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'inspector',
        ]);

        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', 'Account created successfully. Please sign in.');
    }
}
