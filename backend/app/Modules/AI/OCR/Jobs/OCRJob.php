<?php

namespace App\Modules\AI\OCR\Jobs;

use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;
use App\Modules\AI\OCR\Parsers\PdfTextExtractor;
use App\Modules\AI\OCR\Services\DocumentChunkPlanner;
use App\Modules\AI\OCR\Services\OcrService;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OCRJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;
    public $timeout = 300;

    public function __construct(public readonly Document $document)
    {
        $this->onConnection('redis')->onQueue('ocr');
    }

    public function handle(OcrService $ocrService, DocumentChunkPlanner $chunkPlanner, PdfTextExtractor $pdfTextExtractor, IDocumentExtractionRepository $repo, DocumentStatusManager $statusManager): void
    {
        $document = Document::query()->find($this->document->id);

        if ($document === null || $document->status === Document::STATUS_FAILED) {
            return;
        }

        try {
            $tempPath = $ocrService->downloadDocument($document);

            try {
                if ($document->mime_type === 'application/pdf') {
                    $pageCount = $pdfTextExtractor->pageCount($tempPath);

                    if ($chunkPlanner->shouldChunk($pageCount, $document->file_size)) {
                        $this->dispatchChunkJobs($document, $pageCount, $chunkPlanner);

                        return;
                    }
                }

                $result = $ocrService->process($document);
                $repo->upsert($document->id, $result);
                $statusManager->transition($document, Document::STATUS_OCR_COMPLETED);
                OCRCompleted::dispatch($document, $result);
            } finally {
                $ocrService->cleanupTemp($tempPath);
            }
        } catch (Throwable $e) {
            $freshDocument = Document::query()->find($this->document->id);
            if ($freshDocument && $freshDocument->status !== Document::STATUS_FAILED) {
                if ($statusManager->canTransition($freshDocument, Document::STATUS_FAILED)) {
                    $statusManager->transition($freshDocument, Document::STATUS_FAILED);
                } else {
                    $freshDocument->update(['status' => Document::STATUS_FAILED]);
                }
            }
            OCRFailed::dispatch($document ?? $this->document, $e->getMessage());
            throw $e;
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

    /**
     * @param array<int, array{chunk_index:int,page_start:int,page_end:int}> $ranges
     */
    private function dispatchChunkJobs(Document $document, int $pageCount, DocumentChunkPlanner $chunkPlanner): void
    {
        $ranges = $chunkPlanner->plan($pageCount);

        foreach ($ranges as $range) {
            $chunk = DocumentExtractionChunk::create([
                'document_id' => $document->id,
                'chunk_index' => $range['chunk_index'],
                'page_start'  => $range['page_start'],
                'page_end'    => $range['page_end'],
                'status'      => DocumentExtractionChunk::STATUS_PENDING,
            ]);

            OCRChunkJob::dispatch($document, $chunk);
        }
    }
}
