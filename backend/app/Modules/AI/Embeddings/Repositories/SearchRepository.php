<?php

namespace App\Modules\AI\Embeddings\Repositories;

use App\Modules\AI\Embeddings\DTOs\SearchResult;
use App\Modules\AI\Embeddings\Repositories\Contracts\ISearchRepository;
use Illuminate\Support\Facades\DB;

class SearchRepository implements ISearchRepository
{
    public function findSimilarChunks(
        string $organizationId,
        array  $queryEmbedding,
        int    $limit = 10,
    ): array {
        $vectorStr = '[' . implode(',', $queryEmbedding) . ']';

        $rows = DB::select("
            SELECT
                dc.id            AS chunk_id,
                dc.document_id,
                dc.chunk_index,
                dc.text          AS chunk_text,
                d.title          AS document_title,
                d.original_filename,
                1 - (dc.embedding <=> ?::vector) AS score
            FROM document_chunks dc
            JOIN documents d ON d.id = dc.document_id
            WHERE dc.organization_id = ?
              AND dc.embedding IS NOT NULL
              AND d.deleted_at IS NULL
            ORDER BY dc.embedding <=> ?::vector
            LIMIT ?
        ", [$vectorStr, $organizationId, $vectorStr, $limit]);

        return array_map(fn ($row) => new SearchResult(
            chunkId:          $row->chunk_id,
            documentId:       $row->document_id,
            documentTitle:    $row->document_title,
            originalFilename: $row->original_filename,
            chunkText:        $row->chunk_text,
            score:            (float) $row->score,
            chunkIndex:       (int) $row->chunk_index,
        ), $rows);
    }
}
