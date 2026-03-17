<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\ExternalUser;
use App\Models\TestMethod;
use App\Models\TransactionDetail;
use App\Models\TransactionHeader;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_receive_job_can_be_created_updated_closed_reopened_and_deleted(): void
    {
        [$user, $externalUser] = $this->workflowActors();

        $createResponse = $this->actingAs($user)->post(route('receive-job.store'), [
            'external_id' => $externalUser->external_id,
            'internal_id' => $user->user_id,
            'detail' => 'Incoming sample',
            'dmc' => 'DMC-001',
            'line' => 'Line 1',
        ]);

        $job = TransactionHeader::first();

        $createResponse->assertRedirect(route('receive-job.create'));
        $this->assertNotNull($job);
        $this->assertSame('Incoming sample', $job->detail);

        $updateResponse = $this->actingAs($user)->put(route('receive-job.update', $job->transaction_id), [
            'external_id' => $externalUser->external_id,
            'internal_id' => $user->user_id,
            'detail' => 'Updated sample',
            'dmc' => 'DMC-002',
            'line' => 'Line 2',
        ]);

        $updateResponse->assertRedirect(route('receive-job.create'));
        $this->assertSame('Updated sample', $job->fresh()->detail);

        TransactionDetail::create([
            'transaction_id' => $job->transaction_id,
            'method_id' => TestMethod::first()->method_id,
            'internal_id' => $user->user_id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_sec' => 3600,
            'judgement' => TransactionDetail::JUDGEMENT_OK,
            'remark' => 'done',
        ]);

        $closeResponse = $this->actingAs($user)->patch(route('receive-job.close', $job->transaction_id));
        $closeResponse->assertRedirect(route('receive-job.create'));
        $this->assertNotNull($job->fresh()->return_date);

        $reopenResponse = $this->actingAs($user)->patch(route('receive-job.reopen', $job->transaction_id));
        $reopenResponse->assertRedirect(route('receive-job.create'));
        $this->assertNull($job->fresh()->return_date);

        TransactionDetail::query()->delete();

        $deleteResponse = $this->actingAs($user)->delete(route('receive-job.destroy', $job->transaction_id));
        $deleteResponse->assertRedirect(route('receive-job.create'));
        $this->assertSoftDeleted('Transaction_Header', [
            'transaction_id' => $job->transaction_id,
        ]);
    }

    public function test_receive_job_cannot_be_closed_without_test_results(): void
    {
        [$user, $externalUser] = $this->workflowActors();

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $user->user_id,
            'detail' => 'No detail result yet',
            'dmc' => 'DMC-200',
            'line' => 'Line 5',
            'receive_date' => now(),
            'return_date' => null,
        ]);

        $response = $this->actingAs($user)->from(route('receive-job.create'))
            ->patch(route('receive-job.close', $job->transaction_id));

        $response->assertRedirect(route('receive-job.create'));
        $response->assertSessionHas('error');
        $this->assertNull($job->fresh()->return_date);
    }

    public function test_closed_job_with_test_results_can_be_updated_and_deleted(): void
    {
        [$user, $externalUser] = $this->workflowActors();

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $user->user_id,
            'detail' => 'Closed job',
            'dmc' => 'DMC-300',
            'line' => 'Line 6',
            'receive_date' => now(),
            'return_date' => now(),
        ]);

        TransactionDetail::create([
            'transaction_id' => $job->transaction_id,
            'method_id' => TestMethod::first()->method_id,
            'internal_id' => $user->user_id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_sec' => 3600,
            'judgement' => TransactionDetail::JUDGEMENT_OK,
            'remark' => 'done',
        ]);

        $updateResponse = $this->actingAs($user)->put(route('receive-job.update', $job->transaction_id), [
            'external_id' => $externalUser->external_id,
            'internal_id' => $user->user_id,
            'detail' => 'Closed job updated',
            'dmc' => 'DMC-301',
            'line' => 'Line 7',
        ]);

        $updateResponse->assertRedirect(route('receive-job.create'));
        $this->assertSame('Closed job updated', $job->fresh()->detail);

        $deleteResponse = $this->actingAs($user)->delete(route('receive-job.destroy', $job->transaction_id));

        $deleteResponse->assertRedirect(route('receive-job.create'));
        $this->assertSoftDeleted('Transaction_Detail', [
            'transaction_id' => $job->transaction_id,
        ]);
        $this->assertSoftDeleted('Transaction_Header', [
            'transaction_id' => $job->transaction_id,
        ]);
    }

    public function test_execute_test_result_can_be_created_updated_and_deleted(): void
    {
        [$user, $externalUser] = $this->workflowActors();

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $user->user_id,
            'detail' => 'Testing item',
            'dmc' => 'DMC-100',
            'line' => 'Line 3',
            'receive_date' => now(),
        ]);

        $method = TestMethod::first();

        $createResponse = $this->actingAs($user)->post(route('execute-test.store'), [
            'transaction_id' => $job->transaction_id,
            'method_id' => $method->method_id,
            'internal_id' => $user->user_id,
            'start_date' => now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_date' => now()->format('Y-m-d'),
            'end_time' => '09:00',
            'judgement' => TransactionDetail::JUDGEMENT_OK,
            'remark' => 'Initial pass',
        ]);

        $detail = TransactionDetail::first();

        $createResponse->assertRedirect(route('execute-test.create'));
        $this->assertNotNull($detail);
        $this->assertSame('Initial pass', $detail->remark);

        $updateResponse = $this->actingAs($user)->put(route('execute-test.update', $detail->detail_id), [
            'transaction_id' => $job->transaction_id,
            'method_id' => $method->method_id,
            'internal_id' => $user->user_id,
            'start_date' => now()->format('Y-m-d'),
            'start_time' => '08:15',
            'end_date' => now()->format('Y-m-d'),
            'end_time' => '09:30',
            'judgement' => TransactionDetail::JUDGEMENT_NG,
            'remark' => 'Updated result',
        ]);

        $updateResponse->assertRedirect(route('execute-test.create'));
        $this->assertSame(TransactionDetail::JUDGEMENT_NG, $detail->fresh()->judgement);

        $deleteResponse = $this->actingAs($user)->delete(route('execute-test.destroy', $detail->detail_id));

        $deleteResponse->assertRedirect(route('execute-test.create'));
        $this->assertSoftDeleted('Transaction_Detail', [
            'detail_id' => $detail->detail_id,
        ]);
    }

    public function test_admin_can_restore_deleted_job_and_related_details(): void
    {
        [$admin, $externalUser] = $this->workflowActors();

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $admin->user_id,
            'detail' => 'Restorable job',
            'dmc' => 'DMC-500',
            'line' => 'Line 1',
            'receive_date' => now(),
            'return_date' => now(),
        ]);

        $detail = TransactionDetail::create([
            'transaction_id' => $job->transaction_id,
            'method_id' => TestMethod::first()->method_id,
            'internal_id' => $admin->user_id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_sec' => 3600,
            'judgement' => TransactionDetail::JUDGEMENT_OK,
            'remark' => 'restore',
        ]);

        $this->actingAs($admin)->delete(route('receive-job.destroy', $job->transaction_id))->assertRedirect(route('receive-job.create'));
        $this->assertSoftDeleted('Transaction_Header', ['transaction_id' => $job->transaction_id]);
        $this->assertSoftDeleted('Transaction_Detail', ['detail_id' => $detail->detail_id]);

        $this->actingAs($admin)->patch(route('receive-job.restore', $job->transaction_id))->assertRedirect(route('receive-job.create'));
        $this->assertDatabaseHas('Transaction_Header', ['transaction_id' => $job->transaction_id, 'deleted_at' => null]);
        $this->assertDatabaseHas('Transaction_Detail', ['detail_id' => $detail->detail_id, 'deleted_at' => null]);
    }

    public function test_admin_can_restore_deleted_test_result(): void
    {
        [$admin, $externalUser] = $this->workflowActors();

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $admin->user_id,
            'detail' => 'Result restore job',
            'dmc' => 'DMC-510',
            'line' => 'Line 2',
            'receive_date' => now(),
            'return_date' => null,
        ]);

        $detail = TransactionDetail::create([
            'transaction_id' => $job->transaction_id,
            'method_id' => TestMethod::first()->method_id,
            'internal_id' => $admin->user_id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_sec' => 3600,
            'judgement' => TransactionDetail::JUDGEMENT_OK,
            'remark' => 'restore result',
        ]);

        $this->actingAs($admin)->delete(route('execute-test.destroy', $detail->detail_id))
            ->assertRedirect(route('execute-test.create'));

        $this->assertSoftDeleted('Transaction_Detail', ['detail_id' => $detail->detail_id]);

        $this->actingAs($admin)->patch(route('execute-test.restore', $detail->detail_id))
            ->assertRedirect(route('execute-test.create'));

        $this->assertDatabaseHas('Transaction_Detail', [
            'detail_id' => $detail->detail_id,
            'deleted_at' => null,
        ]);
    }

    public function test_non_admin_users_cannot_delete_jobs_or_test_results(): void
    {
        [$admin, $externalUser] = $this->workflowActors();

        $inspector = User::factory()->create([
            'role' => 'inspector',
        ]);

        $job = TransactionHeader::create([
            'external_id' => $externalUser->external_id,
            'internal_id' => $admin->user_id,
            'detail' => 'Protected job',
            'dmc' => 'DMC-401',
            'line' => 'Line 9',
            'receive_date' => now(),
            'return_date' => now(),
        ]);

        $detail = TransactionDetail::create([
            'transaction_id' => $job->transaction_id,
            'method_id' => TestMethod::first()->method_id,
            'internal_id' => $admin->user_id,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'duration_sec' => 3600,
            'judgement' => TransactionDetail::JUDGEMENT_OK,
            'remark' => 'protected',
        ]);

        $jobDeleteResponse = $this->actingAs($inspector)->delete(route('receive-job.destroy', $job->transaction_id));
        $jobDeleteResponse->assertForbidden();

        $detailDeleteResponse = $this->actingAs($inspector)->delete(route('execute-test.destroy', $detail->detail_id));
        $detailDeleteResponse->assertForbidden();

        $jobRestoreResponse = $this->actingAs($inspector)->patch(route('receive-job.restore', $job->transaction_id));
        $jobRestoreResponse->assertForbidden();

        $detailRestoreResponse = $this->actingAs($inspector)->patch(route('execute-test.restore', $detail->detail_id));
        $detailRestoreResponse->assertForbidden();

        $this->assertDatabaseHas('Transaction_Header', [
            'transaction_id' => $job->transaction_id,
        ]);
        $this->assertDatabaseHas('Transaction_Detail', [
            'detail_id' => $detail->detail_id,
        ]);
    }

    private function workflowActors(): array
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $department = Department::create([
            'department_name' => 'QA',
            'internal_phone' => '1234',
        ]);

        $externalUser = ExternalUser::create([
            'external_name' => 'Sender A',
            'department_id' => $department->department_id,
        ]);

        $equipment = Equipment::create([
            'equipment_name' => 'Microscope',
        ]);

        TestMethod::create([
            'method_name' => 'Visual Inspection',
            'tool_name' => 'Scope',
            'equipment_id' => $equipment->equipment_id,
        ]);

        return [$user, $externalUser];
    }
}
