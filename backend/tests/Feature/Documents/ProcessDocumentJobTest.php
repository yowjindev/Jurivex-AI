<?php

namespace Tests\Feature\Documents;

use App\Modules\AI\OCR\Jobs\OCRJob;
use App\Modules\Documents\Jobs\ProcessDocumentJob;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_transitions_to_ocr_processing_and_dispatches_ocr_job(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $job = new ProcessDocumentJob($document);
        $job->handle(app(\App\Modules\Documents\Services\DocumentStatusManager::class));

        $document->refresh();
        $this->assertEquals(Document::STATUS_OCR_PROCESSING, $document->status);
        Queue::assertPushed(OCRJob::class, fn ($job) => $job->document->id === $document->id);
    }

    public function test_handle_dispatches_ocr_job_to_ocr_queue(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $job = new ProcessDocumentJob($document);
        $job->handle(app(\App\Modules\Documents\Services\DocumentStatusManager::class));

        Queue::assertPushedOn('ocr', OCRJob::class);
    }
}
