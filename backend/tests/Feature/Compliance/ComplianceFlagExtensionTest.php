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

    public function test_compliance_flag_type_maps_known_ai_values(): void
    {
        $this->assertEquals(ComplianceFlagType::Risk,     ComplianceFlagType::fromAI('risk'));
        $this->assertEquals(ComplianceFlagType::Deadline, ComplianceFlagType::fromAI('deadline'));
        $this->assertEquals(ComplianceFlagType::Alert,    ComplianceFlagType::fromAI('alert'));
    }

    public function test_compliance_flag_type_case_insensitive(): void
    {
        $this->assertEquals(ComplianceFlagType::Risk, ComplianceFlagType::fromAI('RISK'));
        $this->assertEquals(ComplianceFlagType::Risk, ComplianceFlagType::fromAI('  risk  '));
    }

    public function test_compliance_flag_type_falls_back_to_other(): void
    {
        $this->assertEquals(ComplianceFlagType::Other, ComplianceFlagType::fromAI('hazard'));
        $this->assertEquals(ComplianceFlagType::Other, ComplianceFlagType::fromAI(''));
    }

    public function test_risk_flag_result_holds_all_fields(): void
    {
        $result = new \App\Modules\AI\Risk\DTOs\RiskFlagResult(
            type:        ComplianceFlagType::Risk,
            severity:    'high',
            title:       'Uncapped liability',
            description: 'No cap on damages.',
            explanation: 'Negotiate a damages cap.',
            confidence:  0.9,
        );

        $this->assertSame(ComplianceFlagType::Risk, $result->type);
        $this->assertSame('high', $result->severity);
        $this->assertSame('Uncapped liability', $result->title);
        $this->assertSame('No cap on damages.', $result->description);
        $this->assertSame('Negotiate a damages cap.', $result->explanation);
        $this->assertEquals(0.9, $result->confidence);
    }
}
