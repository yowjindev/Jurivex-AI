<?php

namespace App\Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AI\Models\AiRequest;
use App\Modules\AI\Models\AiTokenBudget;
use App\Modules\AI\Services\TokenBudgetService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIUsageController extends Controller
{
    public function index(): JsonResponse
    {
        $usage = AiRequest::query()
            ->selectRaw("
                organization_id,
                COUNT(*)                                              AS total_requests,
                COALESCE(SUM(total_tokens), 0)                        AS total_tokens,
                COALESCE(SUM(prompt_tokens), 0)                       AS total_input_tokens,
                COALESCE(SUM(completion_tokens), 0)                   AS total_output_tokens,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END)  AS successful_requests,
                SUM(CASE WHEN status = 'failure' THEN 1 ELSE 0 END)  AS failed_requests
            ")
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id');

        $budgets = AiTokenBudget::all()->keyBy('organization_id');
        $orgs    = Organization::all()->keyBy('id');

        $data = $usage->map(function ($row) use ($budgets, $orgs) {
            $budget = $budgets->get($row->organization_id);
            $org    = $orgs->get($row->organization_id);

            return [
                'organization_id'     => $row->organization_id,
                'organization_name'   => $org?->name ?? 'Unknown',
                'total_requests'      => (int) $row->total_requests,
                'total_tokens'        => (int) $row->total_tokens,
                'total_input_tokens'  => (int) $row->total_input_tokens,
                'total_output_tokens' => (int) $row->total_output_tokens,
                'successful_requests' => (int) $row->successful_requests,
                'failed_requests'     => (int) $row->failed_requests,
                'budget'              => $budget ? $this->formatBudget($budget) : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function show(string $orgId): JsonResponse
    {
        $org = Organization::findOrFail($orgId);

        $summary = AiRequest::where('organization_id', $orgId)
            ->selectRaw("
                COUNT(*)                                              AS total_requests,
                COALESCE(SUM(total_tokens), 0)                        AS total_tokens,
                COALESCE(SUM(prompt_tokens), 0)                       AS total_input_tokens,
                COALESCE(SUM(completion_tokens), 0)                   AS total_output_tokens,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END)  AS successful_requests,
                SUM(CASE WHEN status = 'failure' THEN 1 ELSE 0 END)  AS failed_requests,
                ROUND(AVG(latency_ms))                                AS avg_latency_ms
            ")
            ->first();

        $recent = AiRequest::where('organization_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get(['id', 'document_id', 'job_type', 'model', 'total_tokens', 'latency_ms', 'status', 'error_message', 'created_at']);

        $budget = AiTokenBudget::where('organization_id', $orgId)->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'organization_id'   => $org->id,
                'organization_name' => $org->name,
                'summary'           => [
                    'total_requests'      => (int) $summary->total_requests,
                    'total_tokens'        => (int) $summary->total_tokens,
                    'total_input_tokens'  => (int) $summary->total_input_tokens,
                    'total_output_tokens' => (int) $summary->total_output_tokens,
                    'successful_requests' => (int) $summary->successful_requests,
                    'failed_requests'     => (int) $summary->failed_requests,
                    'avg_latency_ms'      => (int) ($summary->avg_latency_ms ?? 0),
                ],
                'budget'          => $budget ? $this->formatBudget($budget) : null,
                'recent_requests' => $recent,
            ],
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function updateBudget(Request $request, string $orgId): JsonResponse
    {
        $request->validate([
            'monthly_token_limit' => 'required|integer|min:0',
            'alert_threshold_pct' => 'integer|min:0|max:100',
        ]);

        Organization::findOrFail($orgId);

        $budget = app(TokenBudgetService::class)->setLimit(
            $orgId,
            $request->integer('monthly_token_limit'),
            $request->integer('alert_threshold_pct', 80),
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'organization_id'      => $budget->organization_id,
                'monthly_token_limit'  => $budget->monthly_token_limit,
                'current_month_tokens' => $budget->current_month_tokens,
                'alert_threshold_pct'  => $budget->alert_threshold_pct,
                'usage_pct'            => $budget->usagePercent(),
                'near_limit'           => $budget->isNearLimit(),
                'exhausted'            => $budget->isExhausted(),
            ],
            'message' => 'Budget updated.',
            'meta'    => [],
        ]);
    }

    private function formatBudget(AiTokenBudget $budget): array
    {
        return [
            'monthly_limit'        => $budget->monthly_token_limit,
            'current_month_tokens' => $budget->current_month_tokens,
            'usage_pct'            => $budget->usagePercent(),
            'period_start'         => $budget->budget_period_start->toDateString(),
            'near_limit'           => $budget->isNearLimit(),
            'exhausted'            => $budget->isExhausted(),
        ];
    }
}
