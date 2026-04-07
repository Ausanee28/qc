<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_request_shows_admin_only_message(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('error', 'Password reset is handled by an administrator. Please provide your employee ID to an admin for a reset.');
    }

    public function test_reset_password_token_flow_redirects_to_admin_reset_notice(): void
    {
        $this->get('/reset-password/test-token')
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('error', 'Password reset is handled by an administrator. Please provide your employee ID to an admin for a reset.');

        $this->post('/reset-password', [
            'token' => 'test-token',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('error', 'Password reset is handled by an administrator. Please provide your employee ID to an admin for a reset.');
    }
}
