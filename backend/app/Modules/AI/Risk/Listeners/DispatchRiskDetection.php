<?php

namespace App\Modules\AI\Risk\Listeners;

use App\Modules\AI\Risk\Jobs\RiskDetectionJob;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;

class DispatchRiskDetection
{
    public function handle(DocumentAnalysisCompleted $event): void
    {
        RiskDetectionJob::dispatch($event->document, $event->analysis);
    }
}
