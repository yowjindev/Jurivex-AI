<?php
// backend/tests/Feature/Documents/DocumentsApiTest.php
namespace Tests\Feature\Documents;

use App\Models\User;
use App\Modules\Documents\Jobs\ProcessDocumentJob;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
        Queue::fake();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_documents(): void
    {
        $this->getJson('/api/v1/documents')->assertStatus(401);
    }

    public function test_admin_can_list_all_org_documents(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        Document::factory()->count(3)->create([
            'organization_id' => $org->id,
            'uploaded_by'     => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/documents')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_staff_only_sees_own_documents(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $other = User::factory()->for($org)->create();
        $other->assignRole('staff');

        Document::factory()->count(2)->create([
            'organization_id' => $org->id,
            'uploaded_by'     => $staff->id,
        ]);
        Document::factory()->create([
            'organization_id' => $org->id,
            'uploaded_by'     => $other->id,
        ]);

        $this->actingAs($staff)
            ->getJson('/api/v1/documents')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_documents_are_scoped_to_own_organization(): void
    {
        $org1  = Organization::factory()->create();
        $admin = User::factory()->for($org1)->create();
        $admin->assignRole('admin');

        $org2 = Organization::factory()->create();
        Document::factory()->count(3)->create(['organization_id' => $org2->id]);

        $this->actingAs($admin)
            ->getJson('/api/v1/documents')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_list_response_includes_pagination_meta(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/documents')
            ->assertStatus(200)
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    // ─── UPLOAD ───────────────────────────────────────────────────────────────

    public function test_any_role_can_upload_a_document(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $file = UploadedFile::fake()->create('contract.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($staff)
            ->postJson('/api/v1/documents', ['file' => $file, 'category' => 'Contract']);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.original_filename', 'contract.pdf')
            ->assertJsonPath('data.category', 'Contract');

        $document = Document::first();
        Storage::disk('s3')->assertExists($document->s3_path);
        Queue::assertPushed(ProcessDocumentJob::class);
    }

    public function test_upload_creates_audit_log(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->create('report.pdf', 512, 'application/pdf');

        $this->actingAs($admin)
            ->postJson('/api/v1/documents', ['file' => $file]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'user_id'         => $admin->id,
            'action'          => 'document.uploaded',
        ]);
    }

    public function test_upload_rejects_disallowed_file_type(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->actingAs($staff)
            ->postJson('/api/v1/documents', ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_files_over_50mb(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $file = UploadedFile::fake()->create('huge.pdf', 52000, 'application/pdf');

        $this->actingAs($staff)
            ->postJson('/api/v1/documents', ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_unauthenticated_cannot_upload(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->postJson('/api/v1/documents', ['file' => $file])->assertStatus(401);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────

    public function test_admin_can_view_any_document_in_org(): void
    {
        $org      = Organization::factory()->create();
        $admin    = User::factory()->for($org)->create();
        $admin->assignRole('admin');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->getJson("/api/v1/documents/{$document->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonStructure(['data' => ['download_url']]);
    }

    public function test_staff_can_view_own_document(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $document = Document::factory()->create([
            'organization_id' => $org->id,
            'uploaded_by'     => $staff->id,
        ]);

        $this->actingAs($staff)
            ->getJson("/api/v1/documents/{$document->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $document->id);
    }

    public function test_staff_cannot_view_another_users_document(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $other    = User::factory()->for($org)->create();
        $document = Document::factory()->create([
            'organization_id' => $org->id,
            'uploaded_by'     => $other->id,
        ]);

        $this->actingAs($staff)
            ->getJson("/api/v1/documents/{$document->id}")
            ->assertStatus(403);
    }

    public function test_cannot_view_document_from_another_org(): void
    {
        $org1  = Organization::factory()->create();
        $admin = User::factory()->for($org1)->create();
        $admin->assignRole('admin');

        $org2     = Organization::factory()->create();
        $document = Document::factory()->create(['organization_id' => $org2->id]);

        $this->actingAs($admin)
            ->getJson("/api/v1/documents/{$document->id}")
            ->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function test_admin_can_update_document_title_and_tags(): void
    {
        $org      = Organization::factory()->create();
        $admin    = User::factory()->for($org)->create();
        $admin->assignRole('admin');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/documents/{$document->id}", [
                'title' => 'Updated Title',
                'tags'  => ['nda', 'urgent'],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.tags', ['nda', 'urgent']);
    }

    public function test_manager_can_update_document(): void
    {
        $org      = Organization::factory()->create();
        $manager  = User::factory()->for($org)->create();
        $manager->assignRole('manager');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($manager)
            ->patchJson("/api/v1/documents/{$document->id}", ['title' => 'New Title'])
            ->assertStatus(200);
    }

    public function test_staff_cannot_update_document(): void
    {
        $org      = Organization::factory()->create();
        $staff    = User::factory()->for($org)->create();
        $staff->assignRole('staff');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($staff)
            ->patchJson("/api/v1/documents/{$document->id}", ['title' => 'Hack'])
            ->assertStatus(403);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_document(): void
    {
        $org      = Organization::factory()->create();
        $admin    = User::factory()->for($org)->create();
        $admin->assignRole('admin');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/documents/{$document->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_delete_creates_audit_log(): void
    {
        $org      = Organization::factory()->create();
        $admin    = User::factory()->for($org)->create();
        $admin->assignRole('admin');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)->deleteJson("/api/v1/documents/{$document->id}");

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action'          => 'document.deleted',
        ]);
    }

    public function test_staff_cannot_delete_document(): void
    {
        $org      = Organization::factory()->create();
        $staff    = User::factory()->for($org)->create();
        $staff->assignRole('staff');
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($staff)
            ->deleteJson("/api/v1/documents/{$document->id}")
            ->assertStatus(403);
    }

    public function test_cannot_delete_document_from_another_org(): void
    {
        $org1  = Organization::factory()->create();
        $admin = User::factory()->for($org1)->create();
        $admin->assignRole('admin');

        $org2     = Organization::factory()->create();
        $document = Document::factory()->create(['organization_id' => $org2->id]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/documents/{$document->id}")
            ->assertStatus(404);
    }
}
