<?php

namespace App\Modules\AI\OCR\Contracts;

use App\Modules\AI\OCR\DTOs\ExtractionResult;

interface TextExtractorContract
{
    public function canHandle(string $mimeType): bool;

    public function extract(string $filePath): ExtractionResult;
}
