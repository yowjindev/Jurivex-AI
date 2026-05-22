<?php

namespace App\Modules\AI\Pipelines;

use App\Modules\AI\Analysis\Jobs\AIAnalysisJob;
use App\Modules\AI\Embeddings\Jobs\EmbeddingJob;
use App\Modules\AI\OCR\Jobs\OCRJob;
use App\Modules\AI\Risk\Jobs\RiskDetectionJob;
use App\Modules\Documents\Models\Document;

class DocumentAnalysisPipeline
{
    /**
     * Phase 2: replace the stub in ProcessDocumentJob with this pipeline.
     * Chains OCR → Analysis → Embedding → RiskDetection as queued jobs.
     */
    public function dispatch(Document $document): void
    {
        // Phase 2:
        // OCRJob::withChain([
        //     new AIAnalysisJob($document),
        //     new EmbeddingJob($document),
        //     new RiskDetectionJob($document),
        // ])->dispatch($document);
    }
}
