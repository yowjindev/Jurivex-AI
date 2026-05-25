<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Organizations\Models\InvitationCode;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvitationCodeFactory extends Factory
{
    protected $model = InvitationCode::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'code'            => fake()->regexify('[A-Z0-9]{8}'),
            'role'            => 'admin',
            'used_by'         => null,
            'used_at'         => null,
            'expires_at'      => null,
        ];
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_by' => User::factory(),
            'used_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
