<?php

namespace Tests\Feature\AI;

use App\Modules\Auth\Models\AuditLog;
use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Listeners\LogOCRActivity;
use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\Documents\Models\Document;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OcrEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocr_completed_event_logs_audit_entry(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $document = Document::factory()->create();
        $result   = new ExtractionResult('text', 2, 10, 50, 'pdf_text', 0.95);

        $listener = new LogOCRActivity();
        $listener->handleCompleted(new OCRCompleted($document, $result));

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $document->organization_id,
            'action'          => 'ocr.completed',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
        ]);

        $log = AuditLog::where('action', 'ocr.completed')->where('auditable_id', $document->id)->first();
        $this->assertEquals('pdf_text', $log->metadata['extractor_type']);
        $this->assertEquals(2, $log->metadata['page_count']);
        $this->assertEquals(10, $log->metadata['word_count']);
        $this->assertEquals(0.95, $log->metadata['confidence']);
    }

    public function test_ocr_failed_event_logs_audit_entry(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $document = Document::factory()->create();

        $listener = new LogOCRActivity();
        $listener->handleFailed(new OCRFailed($document, 'Tesseract binary not found'));

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $document->organization_id,
            'action'          => 'ocr.failed',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
        ]);

        $log = AuditLog::where('action', 'ocr.failed')->where('auditable_id', $document->id)->first();
        $this->assertEquals('Tesseract binary not found', $log->metadata['reason']);
    }

    public function test_ocr_completed_event_is_dispatched_and_listener_fires(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Bus::fake();
        $document = Document::factory()->create();
        $result   = new ExtractionResult('hello', 1, 1, 5, 'image_ocr', 0.85);

        OCRCompleted::dispatch($document, $result);

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'ocr.completed',
            'auditable_id' => $document->id,
        ]);
    }

    public function test_ocr_completed_event_batches_chunk_analysis_for_chunked_documents(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Bus::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_OCR_COMPLETED]);
        DocumentExtractionChunk::factory()->count(2)->sequence(
            ['document_id' => $document->id, 'chunk_index' => 0, 'status' => DocumentExtractionChunk::STATUS_COMPLETED, 'analysis_status' => DocumentExtractionChunk::ANALYSIS_STATUS_PENDING],
            ['document_id' => $document->id, 'chunk_index' => 1, 'status' => DocumentExtractionChunk::STATUS_COMPLETED, 'analysis_status' => DocumentExtractionChunk::ANALYSIS_STATUS_PENDING],
        )->create();

        OCRCompleted::dispatch($document, new ExtractionResult('hello', 1, 1, 5, 'image_ocr', 0.85));

        Bus::assertBatched(function (PendingBatch $batch) use ($document): bool {
            $this->assertCount(2, $batch->jobs);
            $this->assertInstanceOf(\App\Modules\AI\Analysis\Jobs\AIChunkAnalysisJob::class, $batch->jobs[0]);
            $this->assertInstanceOf(\App\Modules\AI\Analysis\Jobs\AIChunkAnalysisJob::class, $batch->jobs[1]);

            return true;
        });
    }

    public function test_service_provider_binds_ocr_service(): void
    {
        $service = $this->app->make(\App\Modules\AI\OCR\Services\OcrService::class);
        $this->assertInstanceOf(\App\Modules\AI\OCR\Services\OcrService::class, $service);
    }

    public function test_service_provider_binds_extraction_repository(): void
    {
        $repo = $this->app->make(\App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository::class);
        $this->assertInstanceOf(\App\Modules\AI\OCR\Repositories\DocumentExtractionRepository::class, $repo);
    }
}
