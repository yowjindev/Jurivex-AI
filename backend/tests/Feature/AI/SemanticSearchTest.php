<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Modules\AI\Contracts\EmbeddingClientContract;
use App\Modules\AI\Embeddings\Models\DocumentChunk;
use App\Modules\Documents\Models\Document;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SemanticSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** Insert a DocumentChunk with a real pgvector embedding for testing. */
    private function seedChunkWithVector(
        string $docId,
        string $orgId,
        string $text,
        int    $idx,
        array  $vector,
    ): DocumentChunk {
        $chunk = DocumentChunk::create([
            'document_id'     => $docId,
            'organization_id' => $orgId,
            'chunk_index'     => $idx,
            'text'            => $text,
            'token_count'     => 10,
            'embedding_model' => 'text-embedding-004',
            'embedded_at'     => now(),
        ]);
        $vectorStr = '[' . implode(',', $vector) . ']';
        DB::statement(
            'UPDATE document_chunks SET embedding = ?::vector WHERE id = ?',
            [$vectorStr, $chunk->id]
        );
        return $chunk;
    }

    /** 768-element float array for test vectors. */
    private function fakeVector(float $value = 0.1): array
    {
        return array_fill(0, 3072, $value);
    }

    public function test_search_returns_relevant_chunks(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = Document::factory()->create([
            'organization_id' => $org->id,
            'title'           => 'Test Contract',
        ]);
        $this->seedChunkWithVector($doc->id, $org->id, 'Clause about payment terms.', 0, $this->fakeVector());

        $mockClient = $this->mock(EmbeddingClientContract::class);
        $mockClient->shouldReceive('embed')->once()->andReturn($this->fakeVector());

        $this->actingAs($user)
            ->getJson('/api/v1/documents/search?q=payment+terms')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.document_id', $doc->id)
            ->assertJsonPath('data.0.document_title', 'Test Contract');
    }

    public function test_search_scopes_results_to_own_organization(): void
    {
        $org1  = Organization::factory()->create();
        $org2  = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org1->id]);
        $user1->assignRole('staff');

        $doc1 = Document::factory()->create(['organization_id' => $org1->id, 'title' => 'Org1 Doc']);
        $doc2 = Document::factory()->create(['organization_id' => $org2->id, 'title' => 'Org2 Doc']);

        $this->seedChunkWithVector($doc1->id, $org1->id, 'Org1 chunk.', 0, $this->fakeVector());
        $this->seedChunkWithVector($doc2->id, $org2->id, 'Org2 chunk.', 0, $this->fakeVector());

        $mockClient = $this->mock(EmbeddingClientContract::class);
        $mockClient->shouldReceive('embed')->once()->andReturn($this->fakeVector());

        $response = $this->actingAs($user1)
            ->getJson('/api/v1/documents/search?q=chunk')
            ->assertStatus(200);

        $documentIds = collect($response->json('data'))->pluck('document_id');
        $this->assertTrue($documentIds->contains($doc1->id));
        $this->assertFalse($documentIds->contains($doc2->id));
    }

    public function test_search_returns_empty_when_no_embedded_chunks(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');

        $mockClient = $this->mock(EmbeddingClientContract::class);
        $mockClient->shouldReceive('embed')->once()->andReturn($this->fakeVector());

        $this->actingAs($user)
            ->getJson('/api/v1/documents/search?q=anything')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_search_validates_query_is_required_and_at_least_2_chars(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->getJson('/api/v1/documents/search')
            ->assertStatus(422);

        $this->actingAs($user)
            ->getJson('/api/v1/documents/search?q=a')
            ->assertStatus(422);
    }

    public function test_unauthenticated_cannot_search(): void
    {
        $this->getJson('/api/v1/documents/search?q=anything')
            ->assertStatus(401);
    }

    public function test_limit_parameter_controls_result_count(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = Document::factory()->create(['organization_id' => $org->id]);

        for ($i = 0; $i < 5; $i++) {
            $this->seedChunkWithVector($doc->id, $org->id, "Chunk {$i}", $i, $this->fakeVector(0.1 + $i * 0.01));
        }

        $mockClient = $this->mock(EmbeddingClientContract::class);
        $mockClient->shouldReceive('embed')->andReturn($this->fakeVector());

        $this->actingAs($user)
            ->getJson('/api/v1/documents/search?q=chunk+text&limit=2')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
