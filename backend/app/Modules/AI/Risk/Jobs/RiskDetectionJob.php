<?php

namespace App\Modules\AI\Risk\Jobs;

use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use App\Modules\AI\Risk\DTOs\RiskFlagResult;
use App\Modules\AI\Contracts\AIClientContract;
use App\Modules\Compliance\Enums\ComplianceFlagType;
use App\Modules\Compliance\Events\ComplianceFlagGenerated;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RiskDetectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 2;
    public $timeout = 120;

    public function __construct(
        public readonly Document         $document,
        public readonly DocumentAnalysis $analysis,
    ) {
        $this->onConnection('redis')->onQueue('analysis');
    }

    public function handle(): void
    {
        $claude       = app(AIClientContract::class);
        $promptLoader = app(PromptLoaderContract::class);
        $repo         = app(IComplianceFlagRepository::class);

        $content  = $this->buildContext();
        $prompt   = $promptLoader->load('document.extract_risks', [
            'content'  => $content,
            'filename' => $this->document->original_filename,
        ]);

        $response = $claude->complete($prompt);
        $parsed   = json_decode($response->content, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($parsed)) {
            Log::warning('RiskDetectionJob: unexpected non-array response from Claude', [
                'document_id' => $this->document->id,
            ]);
            return;
        }

        foreach ($parsed as $item) {
            if (!isset($item['type'], $item['severity'], $item['title'], $item['description'])) {
                continue;
            }

            $flagResult = new RiskFlagResult(
                type:        ComplianceFlagType::fromAI((string) $item['type']),
                severity:    (string) $item['severity'],
                title:       (string) $item['title'],
                description: (string) $item['description'],
                explanation: (string) ($item['explanation'] ?? ''),
                confidence:  (float)  ($item['confidence']  ?? 0.5),
                aiModel:     $response->model,
            );

            $flag = $repo->createFromAI($this->document, $flagResult);
            ComplianceFlagGenerated::dispatch($flag);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('RiskDetectionJob failed', [
            'document_id' => $this->document->id,
            'error'       => $e->getMessage(),
        ]);
    }

    private function buildContext(): string
    {
        $parts = ["Summary: {$this->analysis->summary}"];

        if (!empty($this->analysis->key_points)) {
            $bullets = implode("\n", array_map(fn ($p) => "- {$p}", $this->analysis->key_points));
            $parts[] = "Key Points:\n{$bullets}";
        }

        if (!empty($this->analysis->parties)) {
            $parts[] = 'Parties: ' . implode(', ', $this->analysis->parties);
        }

        if ($this->analysis->governing_law) {
            $parts[] = "Governing Law: {$this->analysis->governing_law}";
        }

        $parts[] = "Risk Score: {$this->analysis->risk_score}";

        return implode("\n\n", $parts);
    }
}
