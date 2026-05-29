<?php

namespace Tests\Feature\AI;

use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Jobs\OCRJob;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;
use App\Modules\AI\OCR\Services\OcrService;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OcrJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_transitions_status_and_dispatches_ocr_completed(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([OCRCompleted::class, OCRFailed::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_OCR_PROCESSING]);
        $result   = new ExtractionResult('text content', 2, 2, 12, 'pdf_text', 1.0);

        $this->mock(OcrService::class, fn ($mock) => $mock->shouldReceive('process')->once()->andReturn($result));
        $this->mock(IDocumentExtractionRepository::class, fn ($mock) => $mock->shouldReceive('upsert')->once());

        $job = new OCRJob($document);
        $job->handle();

        $document->refresh();
        $this->assertEquals(Document::STATUS_OCR_COMPLETED, $document->status);
        Event::assertDispatched(OCRCompleted::class);
        Event::assertNotDispatched(OCRFailed::class);
    }

    public function test_handle_transitions_to_failed_and_rethrows_on_exception(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([OCRCompleted::class, OCRFailed::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->mock(OcrService::class, fn ($mock) => $mock->shouldReceive('process')->once()->andThrow(new \RuntimeException('OCR error')));
        $this->mock(IDocumentExtractionRepository::class);

        $job = new OCRJob($document);

        try {
            $job->handle();
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('OCR error', $e->getMessage());
        }

        $document->refresh();
        $this->assertEquals(Document::STATUS_FAILED, $document->status);
        // OCRFailed is dispatched only from failed(), not from handle() catch —
        // this prevents double-dispatch on the final retry
        Event::assertNotDispatched(OCRFailed::class);
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
}
