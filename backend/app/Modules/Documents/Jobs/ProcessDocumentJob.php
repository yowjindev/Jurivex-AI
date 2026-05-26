<?php

namespace App\Modules\Documents\Jobs;

use App\Modules\AI\OCR\Jobs\OCRJob;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Document $document) {}

    public function handle(DocumentStatusManager $statusManager): void
    {
        $statusManager->transition($this->document, Document::STATUS_OCR_PROCESSING);
        OCRJob::dispatch($this->document);
    }
}
