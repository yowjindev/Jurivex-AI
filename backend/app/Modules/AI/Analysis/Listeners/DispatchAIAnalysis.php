<?php

namespace App\Modules\AI\Analysis\Listeners;

use App\Modules\AI\Analysis\Jobs\AIAnalysisJob;
use App\Modules\AI\OCR\Events\OCRCompleted;

class DispatchAIAnalysis
{
    public function handle(OCRCompleted $event): void
    {
        AIAnalysisJob::dispatch($event->document);
    }
}
