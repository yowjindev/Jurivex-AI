<?php

namespace Tests\Feature\Compliance;

use App\Modules\Compliance\Enums\ComplianceFlagType;
use App\Modules\Compliance\Models\ComplianceFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceFlagExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_compliance_flags_table_stores_ai_fields(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $flag = ComplianceFlag::factory()->create([
            'ai_generated' => true,
            'confidence'   => 0.87,
            'source'       => ComplianceFlag::SOURCE_AI,
            'ai_model'     => 'claude-sonnet-4-6',
            'explanation'  => 'Clause creates significant liability exposure.',
        ]);

        $flag->refresh();

        $this->assertTrue($flag->ai_generated);
        $this->assertEquals(0.87, (float) $flag->confidence);
        $this->assertEquals(ComplianceFlag::SOURCE_AI, $flag->source);
        $this->assertEquals('claude-sonnet-4-6', $flag->ai_model);
        $this->assertEquals('Clause creates significant liability exposure.', $flag->explanation);
    }

    public function test_compliance_flag_has_source_constants(): void
    {
        $this->assertEquals('ai',     ComplianceFlag::SOURCE_AI);
        $this->assertEquals('manual', ComplianceFlag::SOURCE_MANUAL);
    }

    public function test_ai_generated_defaults_to_false(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $flag = ComplianceFlag::factory()->create();
        $this->assertFalse($flag->ai_generated);
        $this->assertNull($flag->confidence);
        $this->assertNull($flag->source);
    }

    public function test_create_from_ai_persists_compliance_flag_with_ai_fields(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $document   = \App\Modules\Documents\Models\Document::factory()->create();
        $repo       = new \App\Modules\Compliance\Repositories\ComplianceFlagRepository();
        $flagResult = new \App\Modules\AI\Risk\DTOs\RiskFlagResult(
            type:        ComplianceFlagType::Risk,
            severity:    'high',
            title:       'Uncapped liability clause',
            description: 'Agreement contains no damages cap.',
            explanation: 'Legal team should negotiate a mutual cap.',
            confidence:  0.88,
        );

        $flag = $repo->createFromAI($document, $flagResult);

        $this->assertDatabaseHas('compliance_flags', [
            'document_id'     => $document->id,
            'organization_id' => $document->organization_id,
            'type'            => 'risk',
            'severity'        => 'high',
            'title'           => 'Uncapped liability clause',
            'ai_generated'    => true,
            'source'          => ComplianceFlag::SOURCE_AI,
            'is_resolved'     => false,
        ]);
        $this->assertEquals(0.88, (float) $flag->confidence);
        $this->assertEquals('Legal team should negotiate a mutual cap.', $flag->explanation);
    }

    public function test_list_by_organization_filters_by_document_id_when_provided(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $org  = \App\Modules\Organizations\Models\Organization::factory()->create();
        $doc1 = \App\Modules\Documents\Models\Document::factory()->create(['organization_id' => $org->id]);
        $doc2 = \App\Modules\Documents\Models\Document::factory()->create(['organization_id' => $org->id]);

        ComplianceFlag::factory()->create(['organization_id' => $org->id, 'document_id' => $doc1->id]);
        ComplianceFlag::factory()->create(['organization_id' => $org->id, 'document_id' => $doc2->id]);

        $repo   = new \App\Modules\Compliance\Repositories\ComplianceFlagRepository();
        $result = $repo->listByOrganization($org->id, 15, $doc1->id);

        $this->assertCount(1, $result->items());
        $this->assertEquals($doc1->id, $result->items()[0]->document_id);
    }

}
