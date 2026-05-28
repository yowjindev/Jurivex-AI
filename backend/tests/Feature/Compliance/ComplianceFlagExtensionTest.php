<?php

namespace Tests\Feature\Compliance;

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

}
