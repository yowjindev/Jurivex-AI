<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Analysis\Jobs\AIAnalysisJob;
use App\Modules\AI\Analysis\Repositories\Contracts\IDocumentAnalysisRepository;
use App\Modules\AI\Services\ClaudeClient;
use App\Modules\AI\Services\ClaudeResponse;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Events\DocumentAnalysisFailed;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AIAnalysisJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeClaudeResponse(array $payload): ClaudeResponse
    {
        return new ClaudeResponse(
            content:      json_encode($payload),
            inputTokens:  500,
            outputTokens: 200,
            model:        'claude-sonnet-4-6-20250514',
        );
    }

    public function test_handle_transitions_status_and_dispatches_analysis_completed(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([DocumentAnalysisCompleted::class, DocumentAnalysisFailed::class]);

        $document = Document::factory()
            ->has(\App\Modules\AI\OCR\Models\DocumentExtraction::factory()->state([
                'extracted_text' => 'This is a legal contract between Acme Corp and Widget Inc.',
            ]), 'extraction')
            ->create(['status' => Document::STATUS_OCR_COMPLETED]);

        $claudeResponse = $this->fakeClaudeResponse([
            'summary'        => 'A contract between Acme and Widget.',
            'key_points'     => ['Party A pays Party B.'],
            'parties'        => ['Acme Corp', 'Widget Inc'],
            'governing_law'  => 'New York, USA',
            'effective_date' => '2024-01-15',
            'risk_score'     => 0.3,
            'confidence'     => 0.9,
        ]);

        $this->mock(ClaudeClient::class, fn ($m) => $m->shouldReceive('complete')->once()->andReturn($claudeResponse));
        $this->mock(IDocumentAnalysisRepository::class, fn ($m) => $m->shouldReceive('upsert')->once()->andReturn(new DocumentAnalysis()));

        $job = new AIAnalysisJob($document);
        $job->handle();

        $document->refresh();
        $this->assertEquals(Document::STATUS_ANALYZED, $document->status);
        Event::assertDispatched(DocumentAnalysisCompleted::class);
        Event::assertNotDispatched(DocumentAnalysisFailed::class);
    }

    public function test_handle_transitions_to_failed_and_rethrows_on_provider_error(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([DocumentAnalysisCompleted::class, DocumentAnalysisFailed::class]);

        $document = Document::factory()
            ->has(\App\Modules\AI\OCR\Models\DocumentExtraction::factory()->state([
                'extracted_text' => 'contract text',
            ]), 'extraction')
            ->create(['status' => Document::STATUS_OCR_COMPLETED]);

        $this->mock(ClaudeClient::class, fn ($m) => $m->shouldReceive('complete')->once()
            ->andThrow(new \App\Exceptions\AI\AIProviderException('Rate limit exceeded')));

        $job = new AIAnalysisJob($document);

        try {
            $job->handle();
            $this->fail('Expected AIProviderException was not thrown');
        } catch (\App\Exceptions\AI\AIProviderException $e) {
            $this->assertSame('Rate limit exceeded', $e->getMessage());
        }

        $document->refresh();
        $this->assertEquals(Document::STATUS_FAILED, $document->status);
        Event::assertNotDispatched(DocumentAnalysisFailed::class);
    }

    public function test_failed_method_sets_status_to_failed_and_dispatches_event(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([DocumentAnalysisFailed::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_AI_PROCESSING]);

        $job = new AIAnalysisJob($document);
        $job->failed(new \RuntimeException('All retries exhausted'));

        $document->refresh();
        $this->assertEquals(Document::STATUS_FAILED, $document->status);
        Event::assertDispatched(DocumentAnalysisFailed::class, fn ($e) => $e->document->id === $document->id);
    }

    public function test_job_has_correct_tries_and_timeout(): void
    {
        $document = Document::factory()->make();
        $job      = new AIAnalysisJob($document);

        $this->assertEquals(2, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }

    public function test_handle_throws_when_document_has_no_extracted_text(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([DocumentAnalysisFailed::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_OCR_COMPLETED]);
        // No extraction record created

        $job = new AIAnalysisJob($document);

        try {
            $job->handle();
            $this->fail('Expected AIAnalysisException was not thrown');
        } catch (\App\Exceptions\AI\AIAnalysisException $e) {
            $this->assertStringContainsString('No extracted text', $e->getMessage());
        }
    }
}
