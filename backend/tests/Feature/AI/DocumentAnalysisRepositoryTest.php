<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Analysis\DTOs\AnalysisResult;
use App\Modules\AI\Analysis\Repositories\DocumentAnalysisRepository;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAnalysisRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_creates_analysis_record(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $document = Document::factory()->create();

        $result = new AnalysisResult(
            summary:       'This is a test contract.',
            keyPoints:     ['Party A must pay Party B.', 'Governing law is New York.'],
            parties:       ['Acme Corp', 'Widget Inc'],
            governingLaw:  'New York, USA',
            effectiveDate: '2024-01-15',
            riskScore:     0.35,
            confidence:    0.90,
            model:         'claude-sonnet-4-6',
            rawResponse:   '{"summary":"This is a test contract."}',
        );

        $repo     = new DocumentAnalysisRepository();
        $analysis = $repo->upsert($document->id, $result);

        $this->assertDatabaseHas('document_analyses', [
            'document_id'   => $document->id,
            'summary'       => 'This is a test contract.',
            'governing_law' => 'New York, USA',
            'ai_model'      => 'claude-sonnet-4-6',
        ]);

        $this->assertSame(['Acme Corp', 'Widget Inc'], $analysis->parties);
        $this->assertSame(['Party A must pay Party B.', 'Governing law is New York.'], $analysis->key_points);
        $this->assertSame($document->id, $analysis->document_id);
    }

    public function test_upsert_updates_existing_record_on_reprocess(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $document = Document::factory()->create();

        $repo = new DocumentAnalysisRepository();

        $first = new AnalysisResult('First summary', [], [], null, null, 0.1, 0.5, 'claude-test', 'raw1');
        $repo->upsert($document->id, $first);

        $second = new AnalysisResult('Updated summary', [], [], null, null, 0.8, 0.95, 'claude-test', 'raw2');
        $repo->upsert($document->id, $second);

        $this->assertDatabaseCount('document_analyses', 1);
        $this->assertDatabaseHas('document_analyses', ['summary' => 'Updated summary']);
    }
}
