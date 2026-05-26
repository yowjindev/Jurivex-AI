<?php

namespace Tests\Feature\AI;

use App\Exceptions\AI\OcrFailedException;
use App\Modules\AI\OCR\Parsers\ImageTextExtractor;
use App\Modules\AI\OCR\Parsers\PdfTextExtractor;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class OcrParserTest extends TestCase
{
    // ── PdfTextExtractor ──────────────────────────────────────────────────────

    public function test_pdf_extractor_handles_pdf_mime_type(): void
    {
        $extractor = new PdfTextExtractor();

        $this->assertTrue($extractor->canHandle('application/pdf'));
        $this->assertFalse($extractor->canHandle('image/jpeg'));
        $this->assertFalse($extractor->canHandle('image/png'));
    }

    public function test_pdf_extractor_returns_text_from_text_based_pdf(): void
    {
        Process::fake([
            "'pdftotext'*" => Process::result("This is the first page of the document.\fThis is the second page of the document."),
        ]);

        $result = (new PdfTextExtractor())->extract('/tmp/test.pdf');

        $this->assertEquals('pdf_text', $result->extractorType);
        $this->assertEquals(2, $result->pageCount);
        $this->assertStringContainsString('This is the first page of the document.', $result->text);
        $this->assertStringContainsString('This is the second page of the document.', $result->text);
        $this->assertEquals(1.0, $result->confidence);
        $this->assertGreaterThan(0, $result->wordCount);
    }

    public function test_pdf_extractor_returns_single_page_when_no_form_feeds(): void
    {
        Process::fake([
            "'pdftotext'*" => Process::result("This single page document contains enough text to pass the extraction threshold check."),
        ]);

        $result = (new PdfTextExtractor())->extract('/tmp/test.pdf');

        $this->assertEquals(1, $result->pageCount);
    }

    // ── ImageTextExtractor ────────────────────────────────────────────────────

    public function test_image_extractor_handles_image_mime_types(): void
    {
        $extractor = new ImageTextExtractor();

        $this->assertTrue($extractor->canHandle('image/jpeg'));
        $this->assertTrue($extractor->canHandle('image/png'));
        $this->assertTrue($extractor->canHandle('image/tiff'));
        $this->assertFalse($extractor->canHandle('application/pdf'));
    }

    public function test_image_extractor_returns_text_from_image(): void
    {
        Process::fake([
            "'tesseract'*" => Process::result("Extracted text from image."),
        ]);

        $result = (new ImageTextExtractor())->extract('/tmp/test.png');

        $this->assertEquals('image_ocr', $result->extractorType);
        $this->assertEquals(1, $result->pageCount);
        $this->assertEquals('Extracted text from image.', $result->text);
        $this->assertEquals(0.85, $result->confidence);
    }

    public function test_image_extractor_throws_ocr_failed_on_error(): void
    {
        Process::fake([
            "'tesseract'*" => Process::result(output: '', exitCode: 1),
        ]);

        $this->expectException(OcrFailedException::class);

        (new ImageTextExtractor())->extract('/tmp/broken.png');
    }
}
