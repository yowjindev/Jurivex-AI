<?php

namespace Tests\Feature\Superadmin;

use App\Models\User;
use App\Modules\AI\Models\AiRequest;
use App\Modules\AI\Models\AiTokenBudget;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIUsageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeAiRequest(string $orgId, array $overrides = []): void
    {
        AiRequest::create(array_merge([
            'organization_id'   => $orgId,
            'document_id'       => null,
            'job_type'          => 'ai_analysis',
            'model'             => 'gemini-test',
            'prompt_tokens'     => 100,
            'completion_tokens' => 50,
            'total_tokens'      => 150,
            'latency_ms'        => 800,
            'status'            => 'success',
        ], $overrides));
    }

    public function test_superadmin_can_list_ai_usage_aggregated_by_org(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $org = Organization::factory()->create(['name' => 'Acme Legal']);
        $this->makeAiRequest($org->id);
        $this->makeAiRequest($org->id, ['prompt_tokens' => 200, 'completion_tokens' => 100, 'total_tokens' => 300]);

        $this->actingAs($superadmin)
            ->getJson('/api/v1/superadmin/ai-usage')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.organization_name', 'Acme Legal')
            ->assertJsonPath('data.0.total_requests', 2)
            ->assertJsonPath('data.0.total_tokens', 450)
            ->assertJsonPath('data.0.successful_requests', 2)
            ->assertJsonPath('data.0.failed_requests', 0);
    }

    public function test_usage_includes_budget_data_when_present(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $org = Organization::factory()->create();
        $this->makeAiRequest($org->id);
        AiTokenBudget::create([
            'organization_id'      => $org->id,
            'monthly_token_limit'  => 1_000_000,
            'current_month_tokens' => 150,
            'alert_threshold_pct'  => 80,
            'budget_period_start'  => now()->startOfMonth()->toDateString(),
        ]);

        $this->actingAs($superadmin)
            ->getJson('/api/v1/superadmin/ai-usage')
            ->assertStatus(200)
            ->assertJsonPath('data.0.budget.monthly_limit', 1_000_000)
            ->assertJsonPath('data.0.budget.current_month_tokens', 150)
            ->assertJsonPath('data.0.budget.exhausted', false);
    }

    public function test_superadmin_can_view_per_org_usage(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $org = Organization::factory()->create(['name' => 'Acme']);
        $this->makeAiRequest($org->id, ['prompt_tokens' => 200, 'completion_tokens' => 100, 'total_tokens' => 300]);
        $this->makeAiRequest($org->id, ['status' => 'failure', 'error_message' => 'timeout', 'model' => 'unknown', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0]);

        $this->actingAs($superadmin)
            ->getJson("/api/v1/superadmin/ai-usage/{$org->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.organization_id', $org->id)
            ->assertJsonPath('data.summary.total_requests', 2)
            ->assertJsonPath('data.summary.total_tokens', 300)
            ->assertJsonPath('data.summary.successful_requests', 1)
            ->assertJsonPath('data.summary.failed_requests', 1);
    }

    public function test_superadmin_can_set_org_ai_budget(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $org = Organization::factory()->create();

        $this->actingAs($superadmin)
            ->putJson("/api/v1/superadmin/organizations/{$org->id}/ai-budget", [
                'monthly_token_limit' => 5_000_000,
                'alert_threshold_pct' => 75,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.monthly_token_limit', 5_000_000)
            ->assertJsonPath('data.alert_threshold_pct', 75);

        $this->assertDatabaseHas('ai_token_budgets', [
            'organization_id'     => $org->id,
            'monthly_token_limit' => 5_000_000,
            'alert_threshold_pct' => 75,
        ]);
    }

    public function test_budget_update_requires_monthly_token_limit(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $org = Organization::factory()->create();

        $this->actingAs($superadmin)
            ->putJson("/api/v1/superadmin/organizations/{$org->id}/ai-budget", [])
            ->assertStatus(422);
    }

    public function test_non_superadmin_cannot_access_ai_usage(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/superadmin/ai-usage')
            ->assertStatus(403);
    }
}
