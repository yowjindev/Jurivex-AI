<?php

namespace App\Modules\AI\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiTokenBudget extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'monthly_token_limit',
        'current_month_tokens',
        'alert_threshold_pct',
        'budget_period_start',
    ];

    protected function casts(): array
    {
        return [
            'monthly_token_limit'  => 'integer',
            'current_month_tokens' => 'integer',
            'alert_threshold_pct'  => 'integer',
            'budget_period_start'  => 'date',
        ];
    }

    public function isExhausted(): bool
    {
        return $this->current_month_tokens >= $this->monthly_token_limit;
    }

    public function isNearLimit(): bool
    {
        if ($this->monthly_token_limit === 0) {
            return false;
        }

        return ($this->current_month_tokens / $this->monthly_token_limit * 100) >= $this->alert_threshold_pct;
    }

    public function usagePercent(): float
    {
        if ($this->monthly_token_limit === 0) {
            return 0.0;
        }

        return round($this->current_month_tokens / $this->monthly_token_limit * 100, 2);
    }
}
