<?php

namespace App\Modules\AI\OCR\Jobs;

use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\AI\OCR\Parsers\PdfTextExtractor;
use App\Modules\AI\OCR\Services\DocumentChunkAggregator;
use App\Modules\AI\OCR\Services\OcrService;
use App\Modules\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OCRChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public readonly Document $document,
        public readonly DocumentExtractionChunk $chunk,
    ) {
        $this->onConnection('redis')->onQueue('ocr');
    }

    public function handle(OcrService $ocrService, DocumentChunkAggregator $aggregator, PdfTextExtractor $pdfTextExtractor): void
    {
        $document = Document::query()->find($this->document->id);

        if ($document === null || $document->status === Document::STATUS_FAILED) {
            return;
        }

        $chunk = DocumentExtractionChunk::query()->find($this->chunk->id);

        if ($chunk === null || $chunk->status === DocumentExtractionChunk::STATUS_COMPLETED) {
            return;
        }

        $tempPath = $ocrService->downloadDocument($document);

        try {
            $chunk->update(['status' => DocumentExtractionChunk::STATUS_PROCESSING]);
            $result = $pdfTextExtractor->extractRange($tempPath, $chunk->page_start, $chunk->page_end);
            $aggregator->recordChunkSuccess($document, $chunk, $result);
        } catch (Throwable $e) {
            $reason = $e->getMessage();
            $aggregator->recordChunkFailure($document, $chunk, $reason);
            OCRFailed::dispatch($document, "Chunk {$chunk->chunk_index} failed: {$reason}");
            throw $e;
        } finally {
            $ocrService->cleanupTemp($tempPath);
        }
    }

    public function failed(Throwable $e): void
    {
        $document = Document::query()->find($this->document->id);

        if ($document === null || $document->status === Document::STATUS_FAILED) {
            return;
        }

        Document::where('id', $this->document->id)->update(['status' => Document::STATUS_FAILED]);
        OCRFailed::dispatch($this->document, $e->getMessage());
    }
}
