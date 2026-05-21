<?php
namespace Tests\Feature\Documents;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_document_factory_creates_record(): void
    {
        $document = Document::factory()->create();

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->assertNotNull($document->id);
        $this->assertEquals('pending', $document->status);
    }

    public function test_document_has_uuid_primary_key(): void
    {
        $document = Document::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $document->id
        );
    }

    public function test_document_belongs_to_organization(): void
    {
        $org = Organization::factory()->create();
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals($org->id, $document->organization->id);
    }

    public function test_document_belongs_to_uploader(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $document = Document::factory()->create([
            'organization_id' => $org->id,
            'uploaded_by' => $user->id,
        ]);

        $this->assertEquals($user->id, $document->uploader->id);
    }

    public function test_document_soft_deletes(): void
    {
        $document = Document::factory()->create();
        $id = $document->id;

        $document->delete();

        $this->assertSoftDeleted('documents', ['id' => $id]);
        $this->assertNull(Document::find($id));
        $this->assertNotNull(Document::withTrashed()->find($id));
    }

    public function test_document_analysis_factory_creates_record(): void
    {
        $document = Document::factory()->create();
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        $this->assertDatabaseHas('document_analyses', ['document_id' => $document->id]);
        $this->assertEquals($document->id, $analysis->document->id);
    }

    public function test_document_tags_cast_to_array(): void
    {
        $document = Document::factory()->create(['tags' => ['nda', 'urgent']]);

        $fresh = Document::find($document->id);
        $this->assertIsArray($fresh->tags);
        $this->assertEquals(['nda', 'urgent'], $fresh->tags);
    }
}
