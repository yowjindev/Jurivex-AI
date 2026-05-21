<?php

namespace Database\Factories;

use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplianceFlag>
 */
class ComplianceFlagFactory extends Factory
{
    protected $model = ComplianceFlag::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'document_id'     => null,
            'type'            => fake()->randomElement([
                ComplianceFlag::TYPE_RISK,
                ComplianceFlag::TYPE_DEADLINE,
                ComplianceFlag::TYPE_ALERT,
            ]),
            'severity'        => fake()->randomElement([
                ComplianceFlag::SEVERITY_LOW,
                ComplianceFlag::SEVERITY_MEDIUM,
                ComplianceFlag::SEVERITY_HIGH,
                ComplianceFlag::SEVERITY_CRITICAL,
            ]),
            'title'           => fake()->sentence(5),
            'description'     => fake()->paragraph(),
            'due_date'        => null,
            'is_resolved'     => false,
        ];
    }

    public function resolved(): static
    {
        return $this->state(['is_resolved' => true]);
    }

    public function deadline(): static
    {
        return $this->state([
            'type'     => ComplianceFlag::TYPE_DEADLINE,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
    }
}
