<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\SchemaCapabilities;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $hasIsActive = SchemaCapabilities::hasColumn('Internal_Users', 'is_active');
        $matchedUserQuery = User::query()
            ->where('user_name', (string) $this->user_name);

        $matchedUser = $hasIsActive
            ? $matchedUserQuery->first(['user_id', 'is_active'])
            : $matchedUserQuery->first(['user_id']);

        if ($hasIsActive && $matchedUser && !$matchedUser->is_active) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'user_name' => 'This account is inactive. Please contact an administrator.',
            ]);
        }

        $credentials = [
            'user_name' => $this->user_name,
            'password' => $this->password,
        ];

        if ($hasIsActive) {
            $credentials['is_active'] = 1;
        }

        $canRemember = SchemaCapabilities::hasColumn('Internal_Users', 'remember_token');
        $remember = $canRemember ? $this->boolean('remember') : false;

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'user_name' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'user_name' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('user_name')) . '|' . $this->ip());
    }
}
