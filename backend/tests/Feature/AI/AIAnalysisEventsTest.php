<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Analysis\Jobs\AIAnalysisJob;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AIAnalysisEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocr_completed_dispatches_ai_analysis_job(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_OCR_COMPLETED]);
        $result   = new ExtractionResult('contract text', 2, 10, 50, 'pdf_text', 1.0);

        OCRCompleted::dispatch($document, $result);

        Queue::assertPushed(AIAnalysisJob::class, fn ($job) => $job->document->id === $document->id);
        Queue::assertPushedOn('analysis', AIAnalysisJob::class);
    }

    public function test_document_analysis_completed_logs_audit_entry(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $document = Document::factory()->create();
        $analysis = DocumentAnalysis::factory()->for($document)->create([
            'risk_score' => 0.45,
            'confidence' => 0.90,
        ]);

        DocumentAnalysisCompleted::dispatch($document, $analysis);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $document->organization_id,
            'action'          => 'document.analyzed',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
        ]);
    }

    public function test_service_provider_binds_ai_client_contract(): void
    {
        $client = $this->app->make(\App\Modules\AI\Contracts\AIClientContract::class);
        $this->assertInstanceOf(\App\Modules\AI\Contracts\AIClientContract::class, $client);
    }

    public function test_service_provider_binds_document_analysis_repository(): void
    {
        $repo = $this->app->make(\App\Modules\AI\Analysis\Repositories\Contracts\IDocumentAnalysisRepository::class);
        $this->assertInstanceOf(\App\Modules\AI\Analysis\Repositories\DocumentAnalysisRepository::class, $repo);
    }
}
