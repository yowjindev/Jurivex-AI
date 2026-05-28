<?php

namespace App\Modules\AI\Analysis\Jobs;

use App\Exceptions\AI\AIAnalysisException;
use App\Modules\AI\Analysis\DTOs\AnalysisResult;
use App\Modules\AI\Analysis\Repositories\Contracts\IDocumentAnalysisRepository;
use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use App\Modules\AI\Services\ClaudeClient;
use App\Modules\AI\Utilities\TextTruncator;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Events\DocumentAnalysisFailed;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AIAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 2;
    public $timeout = 120;

    public function __construct(public readonly Document $document)
    {
        $this->onConnection('redis')->onQueue('analysis');
    }

    public function handle(): void
    {
        $claude        = app(ClaudeClient::class);
        $repo          = app(IDocumentAnalysisRepository::class);
        $statusManager = app(DocumentStatusManager::class);
        $promptLoader  = app(PromptLoaderContract::class);
        $truncator     = app(TextTruncator::class);

        try {
            $statusManager->transition($this->document, Document::STATUS_AI_PROCESSING);

            $this->document->refresh();
            $extraction = $this->document->extraction;

            if (! $extraction?->extracted_text) {
                throw new AIAnalysisException('No extracted text available for analysis.');
            }

            $text   = $truncator->truncate($extraction->extracted_text);
            $prompt = $promptLoader->load('document.analyze', [
                'content'  => $text,
                'filename' => $this->document->original_filename,
            ]);

            $response = $claude->complete($prompt);
            $parsed   = json_decode($response->content, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($parsed['summary'])) {
                throw new AIAnalysisException('Invalid analysis response: missing required summary field.');
            }

            $result = new AnalysisResult(
                summary:       (string) $parsed['summary'],
                keyPoints:     (array) ($parsed['key_points'] ?? []),
                parties:       (array) ($parsed['parties'] ?? []),
                governingLaw:  isset($parsed['governing_law']) ? (string) $parsed['governing_law'] : null,
                effectiveDate: isset($parsed['effective_date']) ? (string) $parsed['effective_date'] : null,
                riskScore:     (float) ($parsed['risk_score'] ?? 0.0),
                confidence:    (float) ($parsed['confidence'] ?? 0.5),
                model:         $response->model,
                rawResponse:   $response->content,
            );

            $analysis = $repo->upsert($this->document->id, $result);

            $statusManager->transition($this->document, Document::STATUS_ANALYZED);

            DocumentAnalysisCompleted::dispatch($this->document, $analysis);

        } catch (Throwable $e) {
            $statusManager->transition($this->document, Document::STATUS_FAILED);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Document::where('id', $this->document->id)->update(['status' => Document::STATUS_FAILED]);
        DocumentAnalysisFailed::dispatch($this->document, $e->getMessage());
    }
}
