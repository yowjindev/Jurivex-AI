<?php

namespace App\Modules\AI\Embeddings\Services;

use App\Modules\AI\Embeddings\DTOs\ChunkResult;

class ChunkingService
{
    private const MAX_CHARS     = 2048;
    private const OVERLAP_CHARS = 200;

    /**
     * Split document text into overlapping semantic chunks.
     *
     * @return ChunkResult[]
     */
    public function chunk(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Split on paragraph / line boundaries, keeping non-empty segments
        $segments = array_values(array_filter(
            preg_split('/\n{2,}|\r\n{2,}/', $text),
            fn (string $s) => trim($s) !== '',
        ));

        // Further split any segment that is itself longer than MAX_CHARS
        $segments = $this->splitLongSegments($segments);

        $chunks  = [];
        $buffer  = '';
        $overlap = '';

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $candidate = $buffer === '' ? $overlap . $segment : $buffer . "\n\n" . $segment;

            if (strlen($candidate) <= self::MAX_CHARS) {
                $buffer = $candidate;
            } else {
                if ($buffer !== '') {
                    $chunks[] = $this->makeChunk(count($chunks), $buffer);
                    $overlap  = $this->tail($buffer, self::OVERLAP_CHARS);
                }
                $buffer  = $overlap . $segment;
                $overlap = '';
            }
        }

        if (trim($buffer) !== '') {
            $chunks[] = $this->makeChunk(count($chunks), $buffer);
        }

        return $chunks;
    }

    /**
     * @param  string[] $segments
     * @return string[]
     */
    private function splitLongSegments(array $segments): array
    {
        $result = [];
        foreach ($segments as $seg) {
            if (strlen($seg) <= self::MAX_CHARS) {
                $result[] = $seg;
                continue;
            }
            // Split on sentence boundaries
            $sentences = preg_split('/(?<=\.)\s+/', $seg, -1, PREG_SPLIT_NO_EMPTY);
            $part      = '';
            foreach ($sentences as $sentence) {
                $candidate = $part === '' ? $sentence : $part . ' ' . $sentence;
                if (strlen($candidate) <= self::MAX_CHARS) {
                    $part = $candidate;
                } else {
                    if ($part !== '') {
                        $result[] = $part;
                    }
                    $part = $sentence;
                }
            }
            if ($part !== '') {
                $result[] = $part;
            }
        }
        return $result;
    }

    private function makeChunk(int $index, string $text): ChunkResult
    {
        $text = trim($text);
        return new ChunkResult(
            chunkIndex: $index,
            text:       $text,
            tokenCount: (int) ceil(strlen($text) / 4),
        );
    }

    private function tail(string $text, int $maxChars): string
    {
        return strlen($text) <= $maxChars
            ? $text
            : substr($text, -$maxChars);
    }
}
