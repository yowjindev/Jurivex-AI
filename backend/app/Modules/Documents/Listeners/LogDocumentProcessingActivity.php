<?php

namespace App\Modules\Documents\Listeners;

use App\Modules\Documents\Events\DocumentProcessingStarted;

class LogDocumentProcessingActivity
{
    public function handle(DocumentProcessingStarted $event): void
    {
        // Phase 2: implement ShouldQueue when adding real work to avoid blocking
        // Phase 2: update real-time status via WebSocket/SSE
    }
}
