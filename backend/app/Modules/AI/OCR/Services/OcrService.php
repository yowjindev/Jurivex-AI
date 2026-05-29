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

    public function downloadDocument(Document $document): string
    {
        $tempPath = sys_get_temp_dir() . '/' . uniqid('ocr_', true) . '_' . basename($document->s3_path);
        $stream = Storage::disk('s3')->readStream($document->s3_path);

        if ($stream === false) {
            throw new \RuntimeException('Unable to read uploaded document from storage.');
        }

        $tempStream = fopen($tempPath, 'w+b');

        if ($tempStream === false) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw new \RuntimeException('Unable to create OCR temp file.');
        }

        try {
            stream_copy_to_stream($stream, $tempStream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            fclose($tempStream);
        }

        return $tempPath;
    }

    public function cleanupTemp(string $tempPath): void
    {
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    public function process(Document $document): ExtractionResult
    {
        $mimeType  = $document->mime_type;
        $extractor = $this->findExtractor($mimeType);

        $tempPath = $this->downloadDocument($document);

        try {
            return $extractor->extract($tempPath);
        } finally {
            $this->cleanupTemp($tempPath);
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
