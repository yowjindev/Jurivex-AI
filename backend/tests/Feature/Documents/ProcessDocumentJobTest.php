<?php

namespace Tests\Feature\Documents;

use App\Modules\AI\OCR\Jobs\OCRJob;
use App\Modules\Documents\Jobs\ProcessDocumentJob;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_transitions_to_ocr_processing_and_dispatches_ocr_job_on_ocr_queue(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $job = new ProcessDocumentJob($document);
        $job->handle(app(DocumentStatusManager::class));

        $document->refresh();
        $this->assertEquals(Document::STATUS_OCR_PROCESSING, $document->status);
        Queue::assertPushed(OCRJob::class, fn ($job) => $job->document->id === $document->id);
        Queue::assertPushedOn('ocr', OCRJob::class);
    }
}
