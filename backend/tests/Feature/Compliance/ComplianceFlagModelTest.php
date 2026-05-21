<?php
namespace Tests\Feature\Compliance;

use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceFlagModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_compliance_flag_factory_creates_record(): void
    {
        $flag = ComplianceFlag::factory()->create();

        $this->assertDatabaseHas('compliance_flags', ['id' => $flag->id]);
        $this->assertFalse($flag->is_resolved);
    }

    public function test_compliance_flag_has_uuid_primary_key(): void
    {
        $flag = ComplianceFlag::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $flag->id
        );
    }

    public function test_compliance_flag_belongs_to_organization(): void
    {
        $org = Organization::factory()->create();
        $flag = ComplianceFlag::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals($org->id, $flag->organization->id);
    }

    public function test_compliance_flag_can_belong_to_document(): void
    {
        $document = Document::factory()->create();
        $flag = ComplianceFlag::factory()->create([
            'organization_id' => $document->organization_id,
            'document_id'     => $document->id,
        ]);

        $this->assertEquals($document->id, $flag->document->id);
    }

    public function test_resolved_factory_state_sets_is_resolved_true(): void
    {
        $flag = ComplianceFlag::factory()->resolved()->create();

        $this->assertTrue($flag->is_resolved);
    }

    public function test_due_date_cast_to_date(): void
    {
        $flag = ComplianceFlag::factory()->create(['due_date' => '2026-12-31']);

        $fresh = ComplianceFlag::find($flag->id);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->due_date);
        $this->assertEquals('2026-12-31', $fresh->due_date->toDateString());
    }
}
