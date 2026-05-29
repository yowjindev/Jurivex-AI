<?php

namespace App\Modules\AI\Services;

use App\Exceptions\AI\AIBudgetExceededException;
use App\Modules\AI\Models\AiTokenBudget;

class TokenBudgetService
{
    /**
     * Throw AIBudgetExceededException if the org has a budget record and it is exhausted.
     * If no budget record exists, the call is allowed (no limit configured).
     */
    public function check(string $organizationId): void
    {
        $budget = AiTokenBudget::where('organization_id', $organizationId)->first();

        if ($budget === null) {
            return;
        }

        if ($budget->isExhausted()) {
            throw new AIBudgetExceededException();
        }
    }

    /**
     * Get or create a budget record for the given org.
     * Default limit: 10 million tokens (permissive for development).
     */
    public function getBudget(string $organizationId): AiTokenBudget
    {
        return AiTokenBudget::firstOrCreate(
            ['organization_id' => $organizationId],
            [
                'monthly_token_limit'  => 10_000_000,
                'current_month_tokens' => 0,
                'alert_threshold_pct'  => 80,
                'budget_period_start'  => now()->startOfMonth()->toDateString(),
            ]
        );
    }

    /**
     * Update the monthly token limit and alert threshold for an org.
     * Creates the budget record if it doesn't exist.
     */
    public function setLimit(string $organizationId, int $limit, int $alertPct = 80): AiTokenBudget
    {
        $budget = $this->getBudget($organizationId);
        $budget->update([
            'monthly_token_limit' => $limit,
            'alert_threshold_pct' => $alertPct,
        ]);

        return $budget->fresh();
    }
}
