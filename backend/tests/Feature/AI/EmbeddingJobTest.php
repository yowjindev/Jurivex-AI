<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Contracts\EmbeddingClientContract;
use App\Modules\AI\Embeddings\Models\DocumentChunk;
use App\Modules\AI\Embeddings\Jobs\EmbeddingJob;
use App\Modules\Documents\Events\DocumentEmbedded;
use App\Modules\Documents\Events\DocumentEmbeddingFailed;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmbeddingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_chunks_table_exists(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->assertTrue(\Schema::hasTable('document_chunks'));
        $this->assertTrue(\Schema::hasColumns('document_chunks', [
            'id', 'document_id', 'organization_id', 'chunk_index',
            'text', 'token_count', 'embedding_model', 'embedded_at',
            'created_at', 'updated_at',
        ]));
    }

    public function test_document_chunks_has_vector_column(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $result = \DB::select("
            SELECT column_name, udt_name
            FROM information_schema.columns
            WHERE table_name = 'document_chunks' AND column_name = 'embedding'
        ");
        $this->assertCount(1, $result);
        $this->assertSame('vector', $result[0]->udt_name);
    }

    public function test_handle_chunks_and_stores_embeddings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::fake([DocumentEmbedded::class, DocumentEmbeddingFailed::class]);

        $org = Organization::factory()->create();
        $doc = Document::factory()->create([
            'organization_id' => $org->id,
            'status'          => Document::STATUS_ANALYZED,
        ]);

        \App\Modules\AI\OCR\Models\DocumentExtraction::create([
            'document_id'    => $doc->id,
            'extracted_text' => 'This is the first paragraph of the legal document. ' .
                                'It contains important terms and conditions.',
            'page_count'     => 1,
            'word_count'     => 20,
            'char_count'     => 100,
            'ocr_engine'     => 'pdftotext',
            'extractor_type' => 'pdf_text',
            'confidence'     => 0.95,
            'extracted_at'   => now(),
        ]);

        $fakeVector = array_fill(0, 768, 0.1);
        $mockClient = $this->mock(\App\Modules\AI\Contracts\EmbeddingClientContract::class);
        $mockClient->shouldReceive('embed')->andReturn($fakeVector);
        $mockClient->shouldReceive('getModel')->andReturn('text-embedding-004');
        $mockClient->shouldReceive('getDimensions')->andReturn(768);

        $job = new EmbeddingJob($doc);
        $job->handle();

        $this->assertGreaterThan(0, \App\Modules\AI\Embeddings\Models\DocumentChunk::where('document_id', $doc->id)->count());
        Event::assertDispatched(DocumentEmbedded::class, fn ($e) => $e->document->id === $doc->id);
        Event::assertNotDispatched(DocumentEmbeddingFailed::class);
    }

    public function test_handle_throws_when_no_extracted_text(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $org = Organization::factory()->create();
        $doc = Document::factory()->create(['organization_id' => $org->id]);
        // No DocumentExtraction record

        $this->mock(\App\Modules\AI\Contracts\EmbeddingClientContract::class);

        $job = new EmbeddingJob($doc);
        $this->expectException(\App\Exceptions\AI\EmbeddingException::class);
        $job->handle();
    }

    public function test_failed_dispatches_embedding_failed_event(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Event::fake([DocumentEmbeddingFailed::class]);

        $org = Organization::factory()->create();
        $doc = Document::factory()->create(['organization_id' => $org->id]);

        $job = new EmbeddingJob($doc);
        $job->failed(new \RuntimeException('connection timeout'));

        Event::assertDispatched(
            DocumentEmbeddingFailed::class,
            fn ($e) => $e->document->id === $doc->id && str_contains($e->reason, 'connection timeout')
        );
    }

    public function test_job_targets_embeddings_queue(): void
    {
        $doc = Document::factory()->make();
        $job = new EmbeddingJob($doc);
        $this->assertSame('embeddings', $job->queue);
    }
}
