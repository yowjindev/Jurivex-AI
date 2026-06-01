<?php

namespace App\Modules\Documents\Services;

use App\Exceptions\Documents\InvalidDocumentTransitionException;
use App\Modules\Documents\Models\Document;

class DocumentStatusManager
{
    private const VALID_TRANSITIONS = [
        Document::STATUS_PENDING        => [Document::STATUS_OCR_PROCESSING],
        Document::STATUS_OCR_PROCESSING => [Document::STATUS_OCR_COMPLETED, Document::STATUS_FAILED],
        Document::STATUS_OCR_COMPLETED  => [Document::STATUS_AI_PROCESSING, Document::STATUS_FAILED],
        Document::STATUS_AI_PROCESSING  => [Document::STATUS_ANALYZED, Document::STATUS_FAILED],
        Document::STATUS_ANALYZED       => [],
        Document::STATUS_FAILED         => [Document::STATUS_PENDING, Document::STATUS_OCR_PROCESSING, Document::STATUS_AI_PROCESSING],
    ];

    public function transition(Document $document, string $newStatus): void
    {
        $currentStatus = $document->status;
        $allowed       = self::VALID_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidDocumentTransitionException($currentStatus, $newStatus);
        }

        $document->update(['status' => $newStatus]);
    }

    public function canTransition(Document $document, string $newStatus): bool
    {
        $allowed = self::VALID_TRANSITIONS[$document->status] ?? [];

        return in_array($newStatus, $allowed, true);
    }
}
