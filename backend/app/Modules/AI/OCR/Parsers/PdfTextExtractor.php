<?php

namespace App\Modules\AI\OCR\Parsers;

use App\Exceptions\AI\OcrFailedException;
use App\Modules\AI\OCR\Contracts\TextExtractorContract;
use App\Modules\AI\OCR\DTOs\ExtractionResult;
use Illuminate\Support\Facades\Process;

class PdfTextExtractor implements TextExtractorContract
{
    public function canHandle(string $mimeType): bool
    {
        return $mimeType === 'application/pdf';
    }

    public function extract(string $filePath): ExtractionResult
    {
        $result = Process::run(['pdftotext', '-layout', $filePath, '-']);

        $rawText   = $result->output();
        $cleanText = str_replace("\f", "\n\n", trim($rawText));
        $pageCount = max(1, substr_count($rawText, "\f") + 1);

        if (strlen(trim($cleanText)) >= 50) {
            return new ExtractionResult(
                text:          $cleanText,
                pageCount:     $pageCount,
                wordCount:     str_word_count($cleanText),
                charCount:     strlen($cleanText),
                extractorType: 'pdf_text',
                confidence:    1.0,
            );
        }

        return $this->extractViaOcr($filePath);
    }

    private function extractViaOcr(string $filePath): ExtractionResult
    {
        $tempDir = sys_get_temp_dir() . '/jurivex_' . uniqid();
        mkdir($tempDir, 0700);

        try {
            $prefix = $tempDir . '/page';
            Process::run(['pdftoppm', '-r', '200', '-png', $filePath, $prefix]);

            $pages = glob($prefix . '-*.png') ?: [];
            sort($pages);

            if (empty($pages)) {
                throw new OcrFailedException('PDF to image conversion produced no pages.');
            }

            $texts = [];
            foreach ($pages as $page) {
                $r       = Process::run(['tesseract', $page, 'stdout', '-l', 'eng']);
                $texts[] = trim($r->output());
            }

            $text = implode("\n\n", $texts);

            return new ExtractionResult(
                text:          $text,
                pageCount:     count($pages),
                wordCount:     str_word_count($text),
                charCount:     strlen($text),
                extractorType: 'pdf_ocr',
                confidence:    0.85,
            );
        } finally {
            foreach (glob($tempDir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($tempDir);
        }
    }
}
