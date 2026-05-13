<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExternalUser;
use App\Models\ProductionLine;
use App\Models\TransactionHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionLineMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_production_lines(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin)->post(route('master-data.lines.store'), [
            'line_name' => 'Line X',
            'sort_order' => 99,
        ]);

        $createResponse->assertRedirect();
        $line = ProductionLine::where('line_name', 'Line X')->first();
        $this->assertNotNull($line);
        $this->assertTrue($line->is_active);

        $updateResponse = $this->actingAs($admin)->put(route('master-data.lines.update', $line->line_id), [
            'line_name' => 'Line X2',
            'sort_order' => 100,
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('Production_Lines', [
            'line_id' => $line->line_id,
            'line_name' => 'Line X2',
            'sort_order' => 100,
        ]);

        $this->actingAs($admin)->patch(route('master-data.lines.set-active', $line->line_id), [
            'is_active' => false,
        ])->assertRedirect();
        $this->assertFalse($line->fresh()->is_active);

        $this->actingAs($admin)->patch(route('master-data.lines.set-active', $line->line_id), [
            'is_active' => true,
        ])->assertRedirect();
        $this->assertTrue($line->fresh()->is_active);
    }

    public function test_non_admin_users_cannot_manage_production_lines(): void
    {
        $inspector = User::factory()->create(['role' => 'inspector']);

        $this->actingAs($inspector)->get(route('master-data.lines.index'))->assertForbidden();
        $this->actingAs($inspector)->post(route('master-data.lines.store'), [
            'line_name' => 'Blocked Line',
            'sort_order' => 1,
        ])->assertForbidden();
    }

    public function test_receive_job_uses_only_active_lines(): void
    {
        [$admin] = $this->workflowActors();
        ProductionLine::where('line_name', 'Line 1')->update(['is_active' => false]);

        $response = $this->actingAs($admin)->get(route('receive-job.create'));

        $response->assertOk();
        $lines = collect(data_get($response->viewData('page'), 'props.lines', []))->pluck('line_name');

        $this->assertFalse($lines->contains('Line 1'));
        $this->assertTrue($lines->contains('Line 2'));
    }

    public function test_receive_job_rejects_unavailable_lines(): void
    {
        [$admin, $externalUser] = $this->workflowActors();

        $response = $this->actingAs($admin)
            ->from(route('receive-job.create'))
            ->post(route('receive-job.store'), [
                'external_id' => $externalUser->external_id,
                'internal_id' => $admin->user_id,
                'detail' => 'Invalid line',
                'dmc' => 'DMC-BAD-LINE',
                'line' => 'Not In Master',
            ]);

        $response->assertRedirect(route('receive-job.create'));
        $response->assertSessionHasErrors(['line']);
        $this->assertDatabaseMissing('Transaction_Header', [
            'dmc' => 'DMC-BAD-LINE',
        ]);
    }

    public function test_existing_inactive_line_can_remain_when_editing_same_job(): void
    {
        [$admin, $externalUser] = $this->workflowActors();
        ProductionLine::where('line_name', 'Line 1')->update(['is_active' => false]);

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $admin->user_id,
            'detail' => 'Legacy inactive line',
            'dmc' => 'DMC-LEGACY-LINE',
            'line' => 'Line 1',
            'receive_date' => now(),
            'return_date' => null,
        ]);

        $response = $this->actingAs($admin)->put(route('receive-job.update', $job->transaction_id), [
            'external_id' => $externalUser->external_id,
            'internal_id' => $admin->user_id,
            'detail' => 'Legacy inactive line updated',
            'dmc' => 'DMC-LEGACY-LINE',
            'line' => 'Line 1',
        ]);

        $response->assertRedirect(route('receive-job.create'));
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Line 1', $job->fresh()->line);
        $this->assertSame('Legacy inactive line updated', $job->fresh()->detail);
    }

    public function test_receive_job_falls_back_to_default_lines_before_migration_runs(): void
    {
        [$admin] = $this->workflowActors();
        Schema::drop('Production_Lines');
        Cache::flush();

        $response = $this->actingAs($admin)->get(route('receive-job.create'));

        $response->assertOk();
        $lines = collect(data_get($response->viewData('page'), 'props.lines', []))->pluck('line_name');

        $this->assertTrue($lines->contains('Line 1'));
        $this->assertTrue($lines->contains('ITT'));
    }

    private function workflowActors(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $department = Department::create([
            'department_name' => 'QA',
            'internal_phone' => '1234',
        ]);

        $externalUser = ExternalUser::create([
            'external_name' => 'Sender A',
            'department_id' => $department->department_id,
        ]);

        return [$admin, $externalUser];
    }
}
