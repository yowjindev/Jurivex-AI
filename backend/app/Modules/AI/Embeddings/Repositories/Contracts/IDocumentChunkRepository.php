<?php

namespace App\Modules\AI\Embeddings\Repositories\Contracts;

use App\Modules\AI\Embeddings\DTOs\ChunkResult;

interface IDocumentChunkRepository
{
    /**
     * Upsert document chunks with their embeddings.
     *
     * @param  ChunkResult[] $chunks
     * @param  float[][]     $embeddings  Indexed parallel to $chunks; null entries skip vector update.
     */
    public function upsertChunks(
        string $documentId,
        string $orgId,
        array  $chunks,
        array  $embeddings,
        string $model,
    ): void;

    public function countByDocument(string $documentId): int;
}
