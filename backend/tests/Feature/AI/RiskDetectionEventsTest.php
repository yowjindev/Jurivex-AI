<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Risk\Jobs\RiskDetectionJob;
use App\Modules\AI\Risk\Listeners\DispatchRiskDetection;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RiskDetectionEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_risk_detection_listener_pushes_job(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);
        $event    = new DocumentAnalysisCompleted($document, $analysis);

        (new DispatchRiskDetection())->handle($event);

        Queue::assertPushed(RiskDetectionJob::class, fn ($job) =>
            $job->document->id === $document->id &&
            $job->analysis->id === $analysis->id
        );
    }

    public function test_document_analysis_completed_event_triggers_dispatch_risk_detection(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Queue::fake();

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        DocumentAnalysisCompleted::dispatch($document, $analysis);

        Queue::assertPushed(RiskDetectionJob::class, fn ($job) => $job->document->id === $document->id);
    }
}
