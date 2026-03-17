<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_access_master_data_pages(): void
    {
        $user = User::factory()->create([
            'role' => 'inspector',
        ]);

        $response = $this->actingAs($user)->get(route('master-data.departments.index'));

        $response->assertForbidden();
    }

    public function test_admin_users_can_access_master_data_pages(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(route('master-data.departments.index'));

        $response->assertOk();
    }
}
