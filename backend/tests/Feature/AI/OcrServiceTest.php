<?php

namespace Tests\Feature\AI;

use App\Exceptions\AI\UnsupportedMimeTypeException;
use App\Modules\AI\OCR\Contracts\TextExtractorContract;
use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Services\OcrService;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OcrServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makePdfExtractor(ExtractionResult $result): TextExtractorContract
    {
        return new class($result) implements TextExtractorContract {
            public function __construct(private ExtractionResult $result) {}
            public function canHandle(string $mimeType): bool { return $mimeType === 'application/pdf'; }
            public function extract(string $filePath): ExtractionResult { return $this->result; }
        };
    }

    public function test_process_downloads_file_and_delegates_to_matching_extractor(): void
    {
        Storage::fake('s3');
        $document = Document::factory()->create(['mime_type' => 'application/pdf', 's3_path' => 'docs/test.pdf']);
        Storage::disk('s3')->put('docs/test.pdf', '%PDF-1.4 test content');

        $expected = new ExtractionResult('Hello world', 1, 2, 11, 'pdf_text', 1.0);
        $service  = new OcrService([$this->makePdfExtractor($expected)]);

        $result = $service->process($document);

        $this->assertSame($expected->text, $result->text);
        $this->assertSame($expected->extractorType, $result->extractorType);
    }

    public function test_process_throws_unsupported_mime_type_when_no_extractor_matches(): void
    {
        Storage::fake('s3');
        $document = Document::factory()->create(['mime_type' => 'application/msword', 's3_path' => 'docs/test.doc']);
        Storage::disk('s3')->put('docs/test.doc', 'content');

        $service = new OcrService([$this->makePdfExtractor(
            new ExtractionResult('x', 1, 1, 1, 'pdf_text', 1.0)
        )]);

        $this->expectException(UnsupportedMimeTypeException::class);
        $service->process($document);
    }

    public function test_process_cleans_up_temp_file_after_extraction(): void
    {
        Storage::fake('s3');
        $document = Document::factory()->create(['mime_type' => 'application/pdf', 's3_path' => 'docs/test.pdf']);
        Storage::disk('s3')->put('docs/test.pdf', '%PDF-1.4 content');

        $extractor = new class implements TextExtractorContract {
            public ?string $capturedPath = null;
            public function canHandle(string $mimeType): bool { return $mimeType === 'application/pdf'; }
            public function extract(string $filePath): ExtractionResult {
                $this->capturedPath = $filePath;
                return new ExtractionResult('text', 1, 1, 4, 'pdf_text', 1.0);
            }
        };

        $service = new OcrService([$extractor]);
        $result  = $service->process($document);

        $this->assertNotNull($extractor->capturedPath);
        $this->assertFileDoesNotExist($extractor->capturedPath);
        $this->assertSame('text', $result->text);
    }

    public function test_upsert_saves_extraction_result_to_database(): void
    {
        $document = Document::factory()->create(['mime_type' => 'application/pdf']);

        $result = new ExtractionResult('extracted text', 3, 2, 13, 'pdf_text', 0.95);

        $repo       = new \App\Modules\AI\OCR\Repositories\DocumentExtractionRepository();
        $extraction = $repo->upsert($document->id, $result);

        $this->assertDatabaseHas('document_extractions', [
            'document_id'    => $document->id,
            'extracted_text' => 'extracted text',
            'page_count'     => 3,
            'extractor_type' => 'pdf_text',
        ]);
        $this->assertSame($document->id, $extraction->document_id);
    }
}
