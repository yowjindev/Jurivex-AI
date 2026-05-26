<?php

namespace App\Modules\AI\OCR\Jobs;

use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;
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

    public function handle(): void
    {
        $ocrService    = app(OcrService::class);
        $repo          = app(IDocumentExtractionRepository::class);
        $statusManager = app(DocumentStatusManager::class);

        try {
            $statusManager->transition($this->document, Document::STATUS_OCR_PROCESSING);
            $result = $ocrService->process($this->document);
            $repo->upsert($this->document->id, $result);
            $statusManager->transition($this->document, Document::STATUS_OCR_COMPLETED);
            OCRCompleted::dispatch($this->document, $result);
        } catch (Throwable $e) {
            $statusManager->transition($this->document, Document::STATUS_FAILED);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Document::where('id', $this->document->id)->update(['status' => Document::STATUS_FAILED]);
        OCRFailed::dispatch($this->document, $e->getMessage());
    }
}
