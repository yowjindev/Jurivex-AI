<?php

namespace Tests\Feature\AI;

use App\Exceptions\AI\AIBudgetExceededException;
use App\Exceptions\AI\AIProviderException;
use App\Modules\AI\Contracts\AIClientContract;
use App\Modules\AI\DTOs\AIResponse;
use App\Modules\AI\Models\AiRequest;
use App\Modules\AI\Models\AiTokenBudget;
use App\Modules\AI\Services\ObservableAIClient;
use App\Modules\AI\Services\TokenBudgetService;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_requests_table_exists(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->assertTrue(\Schema::hasTable('ai_requests'));
        $this->assertTrue(\Schema::hasColumns('ai_requests', [
            'id', 'organization_id', 'document_id', 'job_type', 'model',
            'prompt_tokens', 'completion_tokens', 'total_tokens',
            'latency_ms', 'cost_usd', 'status', 'error_message', 'created_at',
        ]));
        $this->assertFalse(\Schema::hasColumn('ai_requests', 'updated_at'));
    }

    public function test_ai_token_budgets_table_exists(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->assertTrue(\Schema::hasTable('ai_token_budgets'));
        $this->assertTrue(\Schema::hasColumns('ai_token_budgets', [
            'id', 'organization_id', 'monthly_token_limit',
            'current_month_tokens', 'alert_threshold_pct', 'budget_period_start',
            'created_at', 'updated_at',
        ]));
    }

    public function test_token_budget_service_throws_when_exhausted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $org = Organization::factory()->create();
        AiTokenBudget::create([
            'organization_id'      => $org->id,
            'monthly_token_limit'  => 1_000,
            'current_month_tokens' => 1_000,
            'alert_threshold_pct'  => 80,
            'budget_period_start'  => now()->startOfMonth()->toDateString(),
        ]);

        $service = app(TokenBudgetService::class);
        $this->expectException(AIBudgetExceededException::class);
        $service->check($org->id);
    }

    public function test_token_budget_service_passes_when_no_budget_configured(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $org = Organization::factory()->create();
        // No AiTokenBudget record — should NOT throw
        app(TokenBudgetService::class)->check($org->id);
        $this->assertTrue(true);
    }

    public function test_observable_client_creates_ai_request_on_success(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $org      = Organization::factory()->create();
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $mockClient = $this->mock(AIClientContract::class);
        $mockClient->shouldReceive('complete')
            ->once()
            ->andReturn(new AIResponse('the response', 100, 50, 'gemini-test'));

        $client   = new ObservableAIClient($mockClient, $org->id, $document->id, 'ai_analysis');
        $response = $client->complete('test prompt');

        $this->assertSame('the response', $response->content);
        $this->assertDatabaseHas('ai_requests', [
            'organization_id'   => $org->id,
            'document_id'       => $document->id,
            'job_type'          => 'ai_analysis',
            'model'             => 'gemini-test',
            'prompt_tokens'     => 100,
            'completion_tokens' => 50,
            'total_tokens'      => 150,
            'status'            => 'success',
        ]);
    }

    public function test_observable_client_logs_failure_on_provider_exception(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $org      = Organization::factory()->create();
        $document = Document::factory()->create(['organization_id' => $org->id]);

        $mockClient = $this->mock(AIClientContract::class);
        $mockClient->shouldReceive('complete')
            ->once()
            ->andThrow(new AIProviderException('API down'));

        $client = new ObservableAIClient($mockClient, $org->id, $document->id, 'ai_analysis');

        try {
            $client->complete('prompt');
            $this->fail('Expected AIProviderException');
        } catch (AIProviderException) {
            $this->assertDatabaseHas('ai_requests', [
                'organization_id' => $org->id,
                'status'          => 'failure',
                'error_message'   => 'API down',
            ]);
        }
    }

    public function test_observable_client_increments_token_budget(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $org      = Organization::factory()->create();
        $document = Document::factory()->create(['organization_id' => $org->id]);

        AiTokenBudget::create([
            'organization_id'      => $org->id,
            'monthly_token_limit'  => 10_000_000,
            'current_month_tokens' => 500,
            'alert_threshold_pct'  => 80,
            'budget_period_start'  => now()->startOfMonth()->toDateString(),
        ]);

        $mockClient = $this->mock(AIClientContract::class);
        $mockClient->shouldReceive('complete')
            ->once()
            ->andReturn(new AIResponse('ok', 200, 100, 'test'));

        (new ObservableAIClient($mockClient, $org->id, $document->id, 'ai_analysis'))
            ->complete('prompt');

        $this->assertDatabaseHas('ai_token_budgets', [
            'organization_id'      => $org->id,
            'current_month_tokens' => 800,  // 500 + 200 + 100
        ]);
    }
}
