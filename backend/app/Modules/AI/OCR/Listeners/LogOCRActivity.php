<?php

namespace App\Modules\AI\OCR\Listeners;

use App\Modules\Auth\Models\AuditLog;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;

class LogOCRActivity
{
    public function handleCompleted(OCRCompleted $event): void
    {
        AuditLog::create([
            'user_id'         => null,
            'organization_id' => $event->document->organization_id,
            'action'          => 'ocr.completed',
            'auditable_type'  => 'document',
            'auditable_id'    => $event->document->id,
            'metadata'        => [
                'extractor_type' => $event->result->extractorType,
                'page_count'     => $event->result->pageCount,
                'word_count'     => $event->result->wordCount,
                'confidence'     => $event->result->confidence,
            ],
        ]);
    }

    public function handleFailed(OCRFailed $event): void
    {
        AuditLog::create([
            'user_id'         => null,
            'organization_id' => $event->document->organization_id,
            'action'          => 'ocr.failed',
            'auditable_type'  => 'document',
            'auditable_id'    => $event->document->id,
            'metadata'        => ['reason' => $event->reason],
        ]);
    }
}
