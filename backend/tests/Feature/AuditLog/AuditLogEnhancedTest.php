<?php

namespace Tests\Feature\AuditLog;

use App\Modules\Auth\Models\AuditLog;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Documents\Models\Document;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
        Queue::fake();
    }

    public function test_document_upload_audit_log_contains_metadata(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $user->assignRole('admin');

        // fake()->create(name, kilobytes, mimeType) — getSize() returns bytes (1024 * 1024 = 1048576)
        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $this->actingAs($user)->postJson('/api/v1/documents', ['file' => $file]);

        $log = AuditLog::where('action', 'document.uploaded')->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->metadata);
        $this->assertArrayHasKey('mime_type', $log->metadata);
        $this->assertArrayHasKey('file_size', $log->metadata);
        $this->assertSame('application/pdf', $log->metadata['mime_type']);
        $this->assertSame(1048576, $log->metadata['file_size']);
    }

    public function test_flag_resolved_audit_log_contains_metadata(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $user->assignRole('admin');

        $document = Document::factory()->create(['organization_id' => $org->id]);
        $flag = ComplianceFlag::factory()->create([
            'organization_id' => $org->id,
            'document_id'     => $document->id,
            'is_resolved'     => false,
        ]);

        $this->actingAs($user)->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve");

        $log = AuditLog::where('action', 'flag.resolved')->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->metadata);
        $this->assertArrayHasKey('severity', $log->metadata);
        $this->assertArrayHasKey('type', $log->metadata);
    }
}
