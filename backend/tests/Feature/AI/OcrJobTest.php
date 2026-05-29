<?php

namespace Tests\Feature\AI;

use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Jobs\OCRJob;
use App\Modules\AI\OCR\Jobs\OCRChunkJob;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;
use App\Modules\AI\OCR\Services\OcrService;
use App\Modules\AI\OCR\Parsers\PdfTextExtractor;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OcrJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_transitions_status_and_dispatches_ocr_completed(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([OCRCompleted::class, OCRFailed::class]);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_OCR_PROCESSING]);
        $result   = new ExtractionResult('text content', 2, 2, 12, 'pdf_text', 1.0);

        $this->mock(OcrService::class, fn ($mock) => $mock->shouldReceive('downloadDocument')->once()->andReturn('/tmp/doc.pdf')->shouldReceive('process')->once()->andReturn($result)->shouldReceive('cleanupTemp')->atLeast()->once());
        $this->mock(PdfTextExtractor::class, fn ($mock) => $mock->shouldReceive('pageCount')->once()->andReturn(2));
        $this->mock(IDocumentExtractionRepository::class, fn ($mock) => $mock->shouldReceive('upsert')->once());

        $job = new OCRJob($document);
        app()->call([$job, 'handle']);

        $document->refresh();
        $this->assertEquals(Document::STATUS_OCR_COMPLETED, $document->status);
        Event::assertDispatched(OCRCompleted::class);
        Event::assertNotDispatched(OCRFailed::class);
        Queue::assertNothingPushed();
    }

    public function test_handle_transitions_to_failed_and_rethrows_on_exception(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([OCRCompleted::class, OCRFailed::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->mock(OcrService::class, fn ($mock) => $mock->shouldReceive('downloadDocument')->once()->andReturn('/tmp/doc.pdf')->shouldReceive('process')->once()->andThrow(new \RuntimeException('OCR error'))->shouldReceive('cleanupTemp')->atLeast()->once());
        $this->mock(PdfTextExtractor::class, fn ($mock) => $mock->shouldReceive('pageCount')->once()->andReturn(2));
        $this->mock(IDocumentExtractionRepository::class);

        $job = new OCRJob($document);

        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('OCR error', $e->getMessage());
        }

        $document->refresh();
        $this->assertEquals(Document::STATUS_FAILED, $document->status);
        Event::assertDispatched(OCRFailed::class);
        Event::assertNotDispatched(OCRCompleted::class);
    }

    public function test_failed_method_sets_status_to_failed(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([OCRFailed::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_OCR_PROCESSING]);

        $job = new OCRJob($document);
        $job->failed(new \RuntimeException('All retries exhausted'));

        $document->refresh();
        $this->assertEquals(Document::STATUS_FAILED, $document->status);
        Event::assertDispatched(OCRFailed::class, fn ($e) => $e->document->id === $document->id);
    }

    public function test_job_has_correct_tries_and_timeout(): void
    {
        $document = Document::factory()->make();
        $job      = new OCRJob($document);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
    }

    public function test_large_pdf_is_split_into_chunk_jobs(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create([
            'status'     => Document::STATUS_OCR_PROCESSING,
            'mime_type'  => 'application/pdf',
            'file_size'  => 178_800_000,
            's3_path'    => 'org/test/documents/test.pdf',
        ]);

        $this->mock(OcrService::class, fn ($mock) => $mock->shouldReceive('downloadDocument')->once()->andReturn('/tmp/doc.pdf')->shouldReceive('cleanupTemp')->atLeast()->once());
        $this->mock(PdfTextExtractor::class, fn ($mock) => $mock->shouldReceive('pageCount')->once()->andReturn(25));
        $this->mock(IDocumentExtractionRepository::class);

        $job = new OCRJob($document);
        app()->call([$job, 'handle']);

        $this->assertDatabaseCount('document_extraction_chunks', 3);
        Queue::assertPushed(OCRChunkJob::class, 3);
        $document->refresh();
        $this->assertEquals(Document::STATUS_OCR_PROCESSING, $document->status);
    }
}
