<?php

namespace App\Modules\AI\OCR\Repositories;

use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Models\DocumentExtraction;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;

class DocumentExtractionRepository implements IDocumentExtractionRepository
{
    public function upsert(string $documentId, ExtractionResult $result): DocumentExtraction
    {
        /** @var DocumentExtraction $extraction */
        $extraction = DocumentExtraction::updateOrCreate(
            ['document_id' => $documentId],
            [
                'extracted_text' => $result->text,
                'page_count'     => $result->pageCount,
                'word_count'     => $result->wordCount,
                'char_count'     => $result->charCount,
                'ocr_engine'     => 'tesseract',
                'extractor_type' => $result->extractorType,
                'confidence'     => $result->confidence,
                'extracted_at'   => now(),
            ]
        );

        return $extraction;
    }
}
