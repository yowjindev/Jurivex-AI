<?php

namespace Tests\Feature\AI;

use App\Modules\AI\Embeddings\DTOs\ChunkResult;
use App\Modules\AI\Embeddings\Services\ChunkingService;
use Tests\TestCase;

class ChunkingServiceTest extends TestCase
{
    private ChunkingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChunkingService();
    }

    public function test_short_text_produces_single_chunk(): void
    {
        $result = $this->service->chunk('Hello world. This is a test.');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(ChunkResult::class, $result[0]);
        $this->assertSame(0, $result[0]->chunkIndex);
        $this->assertSame('Hello world. This is a test.', $result[0]->text);
        $this->assertGreaterThan(0, $result[0]->tokenCount);
    }

    public function test_long_text_produces_multiple_chunks(): void
    {
        // 10_000-char document — should produce several chunks
        $text   = str_repeat("This is a sentence about legal matters. ", 250);
        $result = $this->service->chunk($text);

        $this->assertGreaterThan(1, count($result));
        foreach ($result as $i => $chunk) {
            $this->assertSame($i, $chunk->chunkIndex);
            $this->assertNotEmpty($chunk->text);
            $this->assertGreaterThan(0, $chunk->tokenCount);
        }
    }

    public function test_chunk_indices_are_sequential_starting_at_zero(): void
    {
        $text   = str_repeat("Paragraph content. ", 300);
        $result = $this->service->chunk($text);

        foreach ($result as $i => $chunk) {
            $this->assertSame($i, $chunk->chunkIndex, "Chunk {$i} has wrong index {$chunk->chunkIndex}");
        }
    }

    public function test_empty_string_produces_no_chunks(): void
    {
        $this->assertCount(0, $this->service->chunk(''));
        $this->assertCount(0, $this->service->chunk('   '));
    }

    public function test_token_count_is_estimated_from_text_length(): void
    {
        $text   = str_repeat('a', 400);   // 400 chars → ceil(400/4) = 100 tokens
        $result = $this->service->chunk($text);

        $this->assertCount(1, $result);
        $this->assertSame((int) ceil(400 / 4), $result[0]->tokenCount);
    }
}
