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
}
