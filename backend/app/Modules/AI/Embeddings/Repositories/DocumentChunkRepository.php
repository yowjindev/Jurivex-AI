<?php

namespace App\Modules\AI\Embeddings\Repositories;

use App\Modules\AI\Embeddings\DTOs\ChunkResult;
use App\Modules\AI\Embeddings\Models\DocumentChunk;
use App\Modules\AI\Embeddings\Repositories\Contracts\IDocumentChunkRepository;
use Illuminate\Support\Facades\DB;

class DocumentChunkRepository implements IDocumentChunkRepository
{
    public function upsertChunks(
        string $documentId,
        string $orgId,
        array  $chunks,
        array  $embeddings,
        string $model,
    ): void {
        foreach ($chunks as $i => $chunk) {
            $embedding = $embeddings[$i] ?? null;

            /** @var DocumentChunk $record */
            $record = DocumentChunk::updateOrCreate(
                ['document_id' => $documentId, 'chunk_index' => $chunk->chunkIndex],
                [
                    'organization_id' => $orgId,
                    'text'            => $chunk->text,
                    'token_count'     => $chunk->tokenCount,
                    'embedding_model' => $embedding !== null ? $model : null,
                    'embedded_at'     => $embedding !== null ? now() : null,
                ]
            );

            if ($embedding !== null) {
                $vectorStr = '[' . implode(',', $embedding) . ']';
                DB::statement(
                    'UPDATE document_chunks SET embedding = ?::vector WHERE id = ?',
                    [$vectorStr, $record->id]
                );
            }
        }
    }

    public function countByDocument(string $documentId): int
    {
        return DocumentChunk::where('document_id', $documentId)->count();
    }
}
