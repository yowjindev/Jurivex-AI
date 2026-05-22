<?php

namespace App\Modules\Documents\Services;

use App\Modules\Documents\Models\Document;
use RuntimeException;

class DocumentStatusManager
{
    private const VALID_TRANSITIONS = [
        Document::STATUS_PENDING    => [Document::STATUS_PROCESSING],
        Document::STATUS_PROCESSING => [Document::STATUS_ANALYZED, Document::STATUS_FAILED],
        Document::STATUS_ANALYZED   => [],
        Document::STATUS_FAILED     => [Document::STATUS_PROCESSING],
    ];

    public function transition(Document $document, string $newStatus): void
    {
        $currentStatus = $document->status;
        $allowed       = self::VALID_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new RuntimeException(
                "Cannot transition document from '{$currentStatus}' to '{$newStatus}'."
            );
        }

        $document->update(['status' => $newStatus]);
    }

    public function canTransition(Document $document, string $newStatus): bool
    {
        $allowed = self::VALID_TRANSITIONS[$document->status] ?? [];

        return in_array($newStatus, $allowed, true);
    }
}
