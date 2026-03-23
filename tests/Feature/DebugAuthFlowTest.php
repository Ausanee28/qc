<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DebugAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_attempt_works_with_factory_user(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Hash::check('password', (string) $user->user_password));
        $this->assertTrue(Auth::attempt([
            'user_name' => $user->user_name,
            'password' => 'password',
        ]));
    }
}

