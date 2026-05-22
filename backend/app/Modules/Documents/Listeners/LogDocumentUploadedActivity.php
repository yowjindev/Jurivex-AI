<?php

namespace App\Modules\Documents\Listeners;

use App\Modules\Documents\Events\DocumentUploaded;

class LogDocumentUploadedActivity
{
    public function handle(DocumentUploaded $event): void
    {
        // Phase 2: add notification dispatch, webhook triggers, etc.
        // Audit log is already written in DocumentService::upload()
        // This listener is the extension point for future integrations.
    }
}
