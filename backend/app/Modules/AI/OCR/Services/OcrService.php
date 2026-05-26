<?php

namespace App\Modules\AI\OCR\Services;

use App\Exceptions\AI\UnsupportedMimeTypeException;
use App\Modules\AI\OCR\Contracts\TextExtractorContract;
use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\Documents\Models\Document;
use Illuminate\Support\Facades\Storage;

class OcrService
{
    /** @param TextExtractorContract[] $extractors */
    public function __construct(private readonly array $extractors) {}

    public function process(Document $document): ExtractionResult
    {
        $mimeType  = $document->mime_type;
        $extractor = $this->findExtractor($mimeType);

        $tempPath = sys_get_temp_dir() . '/' . uniqid('ocr_', true) . '_' . basename($document->s3_path);

        try {
            $contents = Storage::disk('s3')->get($document->s3_path);
            file_put_contents($tempPath, $contents);

            return $extractor->extract($tempPath);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function findExtractor(string $mimeType): TextExtractorContract
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->canHandle($mimeType)) {
                return $extractor;
            }
        }

        throw new UnsupportedMimeTypeException($mimeType);
    }
}
