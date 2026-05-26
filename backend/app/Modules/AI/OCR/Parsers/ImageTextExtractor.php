<?php

namespace App\Modules\AI\OCR\Parsers;

use App\Exceptions\AI\OcrFailedException;
use App\Modules\AI\OCR\Contracts\TextExtractorContract;
use App\Modules\AI\OCR\DTOs\ExtractionResult;
use Illuminate\Support\Facades\Process;

class ImageTextExtractor implements TextExtractorContract
{
    public function canHandle(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/tiff'], true);
    }

    public function extract(string $filePath): ExtractionResult
    {
        $result = Process::run(['tesseract', $filePath, 'stdout', '-l', 'eng']);

        if ($result->failed()) {
            throw new OcrFailedException("Tesseract failed: " . $result->errorOutput());
        }

        $text = trim($result->output());

        return new ExtractionResult(
            text:          $text,
            pageCount:     1,
            wordCount:     str_word_count($text),
            charCount:     strlen($text),
            extractorType: 'image_ocr',
            confidence:    0.85,
        );
    }
}
