<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Risk\Jobs\RiskDetectionJob;
use App\Modules\AI\Services\ClaudeClient;
use App\Modules\AI\Services\ClaudeResponse;
use App\Modules\Compliance\Events\ComplianceFlagGenerated;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RiskDetectionJobTest extends TestCase
{
    use RefreshDatabase;

    private function fakeClaudeResponse(array $flags): ClaudeResponse
    {
        return new ClaudeResponse(
            content:      json_encode($flags),
            inputTokens:  300,
            outputTokens: 200,
            model:        'claude-sonnet-4-6-20250514',
        );
    }

    public function test_handle_creates_flag_and_dispatches_event(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([ComplianceFlagGenerated::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        $this->mock(ClaudeClient::class, fn ($m) => $m->shouldReceive('complete')->once()->andReturn(
            $this->fakeClaudeResponse([[
                'type'        => 'risk',
                'severity'    => 'high',
                'title'       => 'Uncapped liability',
                'description' => 'No damages cap in this agreement.',
                'explanation' => 'Legal team should negotiate a mutual cap.',
                'confidence'  => 0.9,
            ]])
        ));

        $createdFlag = ComplianceFlag::factory()->create(['document_id' => $document->id]);
        $this->mock(IComplianceFlagRepository::class, fn ($m) => $m->shouldReceive('createFromAI')->once()->andReturn($createdFlag));

        (new RiskDetectionJob($document, $analysis))->handle();

        Event::assertDispatched(ComplianceFlagGenerated::class, fn ($e) => $e->flag->id === $createdFlag->id);
    }

    public function test_handle_dispatches_one_event_per_flag(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([ComplianceFlagGenerated::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        $this->mock(ClaudeClient::class, fn ($m) => $m->shouldReceive('complete')->once()->andReturn(
            $this->fakeClaudeResponse([
                ['type' => 'risk',     'severity' => 'high',   'title' => 'Flag A', 'description' => 'Desc A', 'explanation' => 'Exp A', 'confidence' => 0.9],
                ['type' => 'deadline', 'severity' => 'medium', 'title' => 'Flag B', 'description' => 'Desc B', 'explanation' => 'Exp B', 'confidence' => 0.7],
            ])
        ));

        $flag1 = ComplianceFlag::factory()->create();
        $flag2 = ComplianceFlag::factory()->create();
        $this->mock(IComplianceFlagRepository::class, fn ($m) => $m->shouldReceive('createFromAI')->twice()->andReturn($flag1, $flag2));

        (new RiskDetectionJob($document, $analysis))->handle();

        Event::assertDispatchedTimes(ComplianceFlagGenerated::class, 2);
    }

    public function test_handle_skips_malformed_items_and_continues(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([ComplianceFlagGenerated::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        $this->mock(ClaudeClient::class, fn ($m) => $m->shouldReceive('complete')->once()->andReturn(
            $this->fakeClaudeResponse([
                ['type' => 'risk'],
                ['type' => 'alert', 'severity' => 'low', 'title' => 'Valid flag', 'description' => 'Full.', 'explanation' => 'Exp.', 'confidence' => 0.6],
            ])
        ));

        $createdFlag = ComplianceFlag::factory()->create();
        $this->mock(IComplianceFlagRepository::class, fn ($m) => $m->shouldReceive('createFromAI')->once()->andReturn($createdFlag));

        (new RiskDetectionJob($document, $analysis))->handle();

        Event::assertDispatchedTimes(ComplianceFlagGenerated::class, 1);
    }

    public function test_handle_when_claude_returns_empty_array_creates_no_flags(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        Event::fake([ComplianceFlagGenerated::class]);

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        $this->mock(ClaudeClient::class, fn ($m) => $m->shouldReceive('complete')->once()->andReturn(
            $this->fakeClaudeResponse([])
        ));

        $this->mock(IComplianceFlagRepository::class, fn ($m) => $m->shouldReceive('createFromAI')->never());

        (new RiskDetectionJob($document, $analysis))->handle();

        Event::assertNotDispatched(ComplianceFlagGenerated::class);
    }

    public function test_failed_does_not_change_document_status(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);
        $analysis = DocumentAnalysis::factory()->create(['document_id' => $document->id]);

        $job = new RiskDetectionJob($document, $analysis);
        $job->failed(new \RuntimeException('Claude API unavailable'));

        $document->refresh();
        $this->assertEquals(Document::STATUS_ANALYZED, $document->status);
    }

    public function test_job_has_correct_tries_and_timeout(): void
    {
        $document = Document::factory()->make();
        $analysis = new DocumentAnalysis();
        $job      = new RiskDetectionJob($document, $analysis);

        $this->assertEquals(2, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }
}
