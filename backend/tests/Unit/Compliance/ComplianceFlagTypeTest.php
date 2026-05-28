<?php

namespace Tests\Unit\Compliance;

use App\Modules\AI\Risk\DTOs\RiskFlagResult;
use App\Modules\Compliance\Enums\ComplianceFlagType;
use PHPUnit\Framework\TestCase;

class ComplianceFlagTypeTest extends TestCase
{
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
        $result = new RiskFlagResult(
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
