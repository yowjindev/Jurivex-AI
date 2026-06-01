<?php

namespace Tests\Feature\Documents;

use App\Models\User;
use App\Modules\AI\Contracts\AIClientContract;
use App\Modules\AI\Contracts\EmbeddingClientContract;
use App\Modules\AI\DTOs\AIResponse;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentConversation;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('s3');
        Queue::fake();
    }

    private function makeAnalyzedDoc(User $user): Document
    {
        return Document::factory()->create([
            'organization_id' => $user->organization_id,
            'status'          => Document::STATUS_ANALYZED,
        ]);
    }

    private function mockAI(string $content = 'The document says this.'): void
    {
        $this->mock(AIClientContract::class, fn ($m) =>
            $m->shouldReceive('complete')->andReturn(
                new AIResponse($content, 100, 50, 'gemini-test')
            )
        );
        $this->mock(EmbeddingClientContract::class, fn ($m) =>
            $m->shouldReceive('embed')->andReturn(array_fill(0, 768, 0.1))
        );
    }

    public function test_start_conversation_and_receive_answer(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = $this->makeAnalyzedDoc($user);

        $this->mockAI('The answer is in the document.');

        $this->actingAs($user)
            ->postJson("/api/v1/documents/{$doc->id}/conversations", [
                'message' => 'What is this document about?',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [
                'conversation_id', 'message_id', 'content', 'cited_chunks',
                'prompt_tokens', 'completion_tokens', 'model',
            ]]);

        $this->assertDatabaseHas('document_conversations', [
            'document_id' => $doc->id,
            'user_id'     => $user->id,
        ]);
        $this->assertDatabaseCount('conversation_messages', 2); // user + assistant
    }

    public function test_reply_to_existing_conversation(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = $this->makeAnalyzedDoc($user);

        $this->mockAI('The clause is on page 3.');

        $conv = DocumentConversation::create([
            'document_id'     => $doc->id,
            'user_id'         => $user->id,
            'organization_id' => $org->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/documents/{$doc->id}/conversations/{$conv->id}/messages", [
                'message' => 'What does clause 4 say?',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.conversation_id', $conv->id);
    }

    public function test_get_conversation_message_history(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = $this->makeAnalyzedDoc($user);

        $this->mockAI('Yes, this document covers that.');

        $response = $this->actingAs($user)
            ->postJson("/api/v1/documents/{$doc->id}/conversations", [
                'message' => 'First question?',
            ])
            ->assertStatus(201);

        $convId = $response->json('data.conversation_id');

        $this->actingAs($user)
            ->getJson("/api/v1/documents/{$doc->id}/conversations/{$convId}/messages")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.role', 'user')
            ->assertJsonPath('data.1.role', 'assistant');
    }

    public function test_list_conversations_for_document(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = $this->makeAnalyzedDoc($user);

        DocumentConversation::create(['document_id' => $doc->id, 'user_id' => $user->id, 'organization_id' => $org->id]);
        DocumentConversation::create(['document_id' => $doc->id, 'user_id' => $user->id, 'organization_id' => $org->id]);

        $this->actingAs($user)
            ->getJson("/api/v1/documents/{$doc->id}/conversations")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_chat_with_unanalyzed_document(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('staff');
        $doc  = Document::factory()->create([
            'organization_id' => $org->id,
            'status'          => Document::STATUS_OCR_COMPLETED,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/documents/{$doc->id}/conversations", [
                'message' => 'Hello?',
            ])
            ->assertStatus(403);
    }

    public function test_cannot_access_another_users_conversation(): void
    {
        $org   = Organization::factory()->create();
        $user1 = User::factory()->create(['organization_id' => $org->id]);
        $user2 = User::factory()->create(['organization_id' => $org->id]);
        $user1->assignRole('staff');
        $user2->assignRole('staff');
        $doc   = $this->makeAnalyzedDoc($user1);

        $conv = DocumentConversation::create([
            'document_id'     => $doc->id,
            'user_id'         => $user1->id,
            'organization_id' => $org->id,
        ]);

        $this->actingAs($user2)
            ->postJson("/api/v1/documents/{$doc->id}/conversations/{$conv->id}/messages", [
                'message' => 'Can I see this?',
            ])
            ->assertStatus(404);
    }

    public function test_unauthenticated_cannot_chat(): void
    {
        $org = Organization::factory()->create();
        $doc = Document::factory()->create([
            'organization_id' => $org->id,
            'status'          => Document::STATUS_ANALYZED,
        ]);

        $this->postJson("/api/v1/documents/{$doc->id}/conversations", [
            'message' => 'Hello',
        ])->assertStatus(401);
    }
}
