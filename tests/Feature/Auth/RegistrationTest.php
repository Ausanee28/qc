<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $email = 'test@example.com';

        $response = $this->post('/register', [
            'user_name' => 'test.user',
            'employee_id' => 'EMP001',
            'name' => 'Test User',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', 'Account created successfully. Please sign in.');
        $this->assertGuest();
        $this->assertDatabaseHas('Internal_Users', [
            'email' => $email,
        ]);
        Notification::assertSentTo(
            User::where('email', $email)->firstOrFail(),
            VerifyEmail::class
        );
    }
}
