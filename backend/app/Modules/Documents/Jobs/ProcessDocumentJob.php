<?php

namespace App\Modules\Documents\Jobs;

use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Events\DocumentProcessingStarted;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Document $document) {}

    public function handle(DocumentStatusManager $statusManager): void
    {
        $statusManager->transition($this->document, Document::STATUS_PROCESSING);
        DocumentProcessingStarted::dispatch($this->document);

        // Phase 2: replace these two lines with DocumentAnalysisPipeline::dispatch()
        $statusManager->transition($this->document, Document::STATUS_ANALYZED);
        DocumentAnalysisCompleted::dispatch($this->document);
    }
}
