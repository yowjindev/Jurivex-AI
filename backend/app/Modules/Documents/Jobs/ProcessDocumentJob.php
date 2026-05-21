<?php
namespace App\Modules\Documents\Jobs;

use App\Modules\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $this->document->update(['status' => Document::STATUS_PROCESSING]);
        // Phase 2: chain OCRJob → AIAnalysisJob → EmbeddingJob → RiskDetectionJob
        $this->document->update(['status' => Document::STATUS_ANALYZED]);
    }
}
