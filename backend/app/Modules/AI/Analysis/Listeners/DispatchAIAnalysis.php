<?php

namespace App\Modules\AI\Analysis\Listeners;

use App\Modules\AI\Analysis\Jobs\AIChunkAnalysisJob;
use App\Modules\AI\Analysis\Jobs\AIAnalysisJob;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DispatchAIAnalysis
{
    public function handle(OCRCompleted $event): void
    {
        $document = Document::query()->with('chunks')->find($event->document->id);

        if ($document === null || $document->status === Document::STATUS_FAILED) {
            return;
        }

        $chunks = $document->chunks()->orderBy('chunk_index')->get();

        if ($chunks->isEmpty()) {
            AIAnalysisJob::dispatch($document);
            return;
        }

        $statusManager = app(DocumentStatusManager::class);
        if ($statusManager->canTransition($document, Document::STATUS_AI_PROCESSING)) {
            $statusManager->transition($document, Document::STATUS_AI_PROCESSING);
        }

        // Stagger dispatch times to stay within the AI provider's RPM limit.
        // interval = ceil(60 / RPM) seconds between each chunk job.
        $rpm      = (int) config('ai.gemini.requests_per_minute', 5);
        $interval = (int) ceil(60 / max(1, $rpm));

        $pendingChunks = $chunks
            ->where('status', DocumentExtractionChunk::STATUS_COMPLETED)
            ->where('analysis_status', '!=', DocumentExtractionChunk::ANALYSIS_STATUS_COMPLETED)
            ->values();

        $jobs = $pendingChunks
            ->map(function (DocumentExtractionChunk $chunk, int $index) use ($document, $interval) {
                $job = new AIChunkAnalysisJob($document, $chunk);
                if ($index > 0) {
                    $job->delay(now()->addSeconds($index * $interval));
                }
                return $job;
            })
            ->all();

        if ($jobs === []) {
            AIAnalysisJob::dispatch($document);
            return;
        }

        Bus::batch($jobs)
            ->name("document-analysis:{$document->id}")
            ->then(function (Batch $batch) use ($document): void {
                AIAnalysisJob::dispatch($document);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($document): void {
                Document::where('id', $document->id)->update(['status' => Document::STATUS_FAILED]);
            })
            ->dispatch();
    }
}
