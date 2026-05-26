<?php

namespace App\Modules\AI\OCR\DTOs;

readonly class ExtractionResult
{
    public function __construct(
        public string $text,
        public int    $pageCount,
        public int    $wordCount,
        public int    $charCount,
        public string $extractorType,
        public float  $confidence = 1.0,
    ) {}
}
