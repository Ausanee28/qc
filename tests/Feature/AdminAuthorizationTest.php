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

    public function test_non_admin_inertia_visits_receive_forbidden_page(): void
    {
        $user = User::factory()->create([
            'role' => 'inspector',
        ]);
        $manifestPath = public_path('build/manifest.json');
        $inertiaVersion = file_exists($manifestPath) ? hash_file('xxh128', $manifestPath) : '';

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', $inertiaVersion)
            ->get(route('master-data.departments.index'));

        $response->assertForbidden()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Errors/Forbidden');
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
