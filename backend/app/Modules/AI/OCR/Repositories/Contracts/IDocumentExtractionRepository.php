<?php

namespace App\Modules\AI\OCR\Repositories\Contracts;

use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Models\DocumentExtraction;

interface IDocumentExtractionRepository
{
    public function upsert(string $documentId, ExtractionResult $result): DocumentExtraction;
}
