<?php

namespace App\Modules\AI\Analysis\Jobs;

use App\Exceptions\AI\AIAnalysisException;
use App\Modules\AI\Analysis\DTOs\AnalysisResult;
use App\Modules\AI\Analysis\Repositories\Contracts\IDocumentAnalysisRepository;
use App\Modules\AI\Contracts\AIClientContract;
use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use App\Modules\Documents\Events\DocumentAnalysisFailed;
use App\Modules\Documents\Models\Document;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AIChunkAnalysisJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    public function __construct(
        public readonly Document $document,
        public readonly DocumentExtractionChunk $chunk,
    ) {
        $this->onConnection('redis')->onQueue('analysis');
    }

    public function handle(): void
    {
        $document = Document::query()->find($this->document->id);
        if ($document === null || $document->status === Document::STATUS_FAILED) {
            return;
        }

        $chunk = DocumentExtractionChunk::query()->find($this->chunk->id);
        if ($chunk === null || $chunk->status !== DocumentExtractionChunk::STATUS_COMPLETED) {
            return;
        }

        if (! filled($chunk->extracted_text)) {
            throw new AIAnalysisException('No extracted text available for chunk analysis.');
        }

        $claude       = app(AIClientContract::class);
        $promptLoader = app(PromptLoaderContract::class);

        try {
            $chunk->update([
                'analysis_status' => DocumentExtractionChunk::ANALYSIS_STATUS_PROCESSING,
                'analysis_error_message' => null,
            ]);

            $prompt = $promptLoader->load('document.analyze_chunk', [
                'filename'    => $document->original_filename,
                'chunk_range' => "pages {$chunk->page_start}-{$chunk->page_end}",
                'content'     => $chunk->extracted_text,
            ]);

            $response = $claude->complete($prompt);
            $parsed = json_decode($response->content, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($parsed['summary'])) {
                throw new AIAnalysisException('Invalid chunk analysis response: missing required summary field.');
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

            $chunk->update([
                'analysis_status'         => DocumentExtractionChunk::ANALYSIS_STATUS_COMPLETED,
                'analysis_summary'        => $result->summary,
                'analysis_key_points'     => $result->keyPoints,
                'analysis_parties'        => $result->parties,
                'analysis_governing_law'  => $result->governingLaw,
                'analysis_effective_date' => $result->effectiveDate,
                'analysis_risk_score'     => $result->riskScore,
                'analysis_confidence'     => $result->confidence,
                'analysis_model'          => $result->model,
                'analysis_raw_response'   => $result->rawResponse,
                'analyzed_at'             => now(),
            ]);
        } catch (Throwable $e) {
            $chunk->update([
                'analysis_status'        => DocumentExtractionChunk::ANALYSIS_STATUS_FAILED,
                'analysis_error_message' => $e->getMessage(),
            ]);

            Document::where('id', $document->id)->update(['status' => Document::STATUS_FAILED]);
            DocumentAnalysisFailed::dispatch($document, "Chunk {$chunk->chunk_index} analysis failed: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $document = Document::query()->find($this->document->id);
        if ($document === null || $document->status === Document::STATUS_FAILED) {
            return;
        }

        Document::where('id', $document->id)->update(['status' => Document::STATUS_FAILED]);
        DocumentAnalysisFailed::dispatch($this->document, $e->getMessage());
    }
}
