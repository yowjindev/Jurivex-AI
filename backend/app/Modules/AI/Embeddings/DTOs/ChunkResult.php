<?php

namespace App\Modules\AI\Embeddings\DTOs;

readonly class ChunkResult
{
    public function __construct(
        public int    $chunkIndex,
        public string $text,
        public int    $tokenCount,  // estimated: ceil(strlen / 4)
    ) {}
}
