<?php

namespace App\Modules\AI\OCR\Events;

use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OCRFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Document $document,
        public readonly string $reason,
    ) {}
}
