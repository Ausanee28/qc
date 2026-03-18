<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_export_sanitizes_download_filename(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('report.export', [
            'ids' => 'all',
            'filename' => "bad\r\nname/\\:*?\"<>|",
        ]));

        $response->assertOk();

        $disposition = (string) $response->headers->get('Content-Disposition');

        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
        $this->assertStringNotContainsString('/', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }
}

