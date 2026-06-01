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
}
