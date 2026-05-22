<?php

namespace App\Modules\Documents\Listeners;

use App\Modules\Documents\Events\DocumentAnalysisCompleted;

class LogDocumentAnalysisActivity
{
    public function handle(DocumentAnalysisCompleted $event): void
    {
        // Phase 2: dispatch NotificationJob, update user dashboard metrics
    }
}
