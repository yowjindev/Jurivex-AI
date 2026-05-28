<?php

namespace App\Modules\Documents\Listeners;

use App\Modules\Auth\Models\AuditLog;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;

class LogDocumentAnalysisActivity
{
    public function handle(DocumentAnalysisCompleted $event): void
    {
        AuditLog::create([
            'user_id'         => null,
            'organization_id' => $event->document->organization_id,
            'action'          => 'document.analyzed',
            'auditable_type'  => 'document',
            'auditable_id'    => $event->document->id,
            'metadata'        => [
                'ai_model'   => $event->analysis->ai_model,
                'risk_score' => $event->analysis->risk_score,
                'confidence' => $event->analysis->confidence,
            ],
        ]);
    }
}
