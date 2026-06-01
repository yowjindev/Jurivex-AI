<?php

namespace App\Modules\AI\Embeddings\DTOs;

readonly class SearchResult
{
    public function __construct(
        public string $chunkId,
        public string $documentId,
        public string $documentTitle,
        public string $originalFilename,
        public string $chunkText,
        public float  $score,          // 0.0–1.0, higher = more relevant
        public int    $chunkIndex,
    ) {}
}
