<?php

namespace App\Modules\AI\OCR\Events;

use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OCRCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Document $document,
        public readonly ExtractionResult $result,
    ) {}
}
