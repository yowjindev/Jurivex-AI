<?php

namespace App\Console\Commands;

use App\Modules\AI\Models\AiTokenBudget;
use Illuminate\Console\Command;

class ResetMonthlyAIBudgets extends Command
{
    protected $signature   = 'ai:reset-monthly-budgets';
    protected $description = 'Reset current_month_tokens to 0 for all orgs at the start of a new billing period';

    public function handle(): int
    {
        $count = AiTokenBudget::count();

        AiTokenBudget::query()->update([
            'current_month_tokens' => 0,
            'budget_period_start'  => now()->startOfMonth()->toDateString(),
        ]);

        $this->info("Reset monthly AI budgets for {$count} organization(s).");

        return Command::SUCCESS;
    }
}
