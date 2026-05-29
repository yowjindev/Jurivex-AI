<?php

namespace App\Modules\AI\OCR\Services;

class DocumentChunkPlanner
{
    public const DEFAULT_CHUNK_SIZE = 10;
    public const MIN_PAGE_THRESHOLD = 10;

    public function shouldChunk(int $pageCount, int $fileSize): bool
    {
        return $pageCount > self::MIN_PAGE_THRESHOLD || $fileSize > 25 * 1024 * 1024;
    }

    /**
     * @return array<int, array{chunk_index:int,page_start:int,page_end:int}>
     */
    public function plan(int $pageCount): array
    {
        $ranges = [];
        $chunkIndex = 0;

        for ($pageStart = 1; $pageStart <= $pageCount; $pageStart += self::DEFAULT_CHUNK_SIZE) {
            $pageEnd = min($pageStart + self::DEFAULT_CHUNK_SIZE - 1, $pageCount);
            $ranges[] = [
                'chunk_index' => $chunkIndex++,
                'page_start'  => $pageStart,
                'page_end'    => $pageEnd,
            ];
        }

        return $ranges;
    }
}

