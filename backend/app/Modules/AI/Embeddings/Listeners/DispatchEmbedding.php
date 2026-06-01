<?php

namespace App\Modules\AI\Embeddings\Listeners;

use App\Modules\AI\Embeddings\Jobs\EmbeddingJob;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;

class DispatchEmbedding
{
    public function handle(DocumentAnalysisCompleted $event): void
    {
        EmbeddingJob::dispatch($event->document);
    }
}
