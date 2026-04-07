<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        return redirect()
            ->route('password.request')
            ->with('error', 'Password reset is handled by an administrator. Please provide your employee ID to an admin for a reset.');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()
            ->route('password.request')
            ->with('error', 'Password reset is handled by an administrator. Please provide your employee ID to an admin for a reset.');
    }
}
