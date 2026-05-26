<?php

namespace Tests\Feature\Exceptions;

use App\Exceptions\AI\OcrFailedException;
use App\Exceptions\AI\UnsupportedMimeTypeException;
use App\Exceptions\AppException;
use Tests\TestCase;

class OcrExceptionTest extends TestCase
{
    public function test_ocr_failed_exception_is_app_exception(): void
    {
        $e = new OcrFailedException('Tesseract timed out');

        $this->assertInstanceOf(AppException::class, $e);
        $this->assertEquals(500, $e->statusCode);
        $this->assertEquals('Tesseract timed out', $e->getMessage());
    }

    public function test_ocr_failed_exception_has_default_message(): void
    {
        $e = new OcrFailedException();

        $this->assertEquals('OCR extraction failed.', $e->getMessage());
    }

    public function test_unsupported_mime_type_exception_is_app_exception(): void
    {
        $e = new UnsupportedMimeTypeException('video/mp4');

        $this->assertInstanceOf(AppException::class, $e);
        $this->assertEquals(422, $e->statusCode);
        $this->assertStringContainsString('video/mp4', $e->getMessage());
    }
}
