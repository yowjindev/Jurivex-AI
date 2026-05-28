<?php

namespace App\Modules\AI\Utilities;

class TextTruncator
{
    private const MAX_CHARS  = 80_000;
    private const HEAD_CHARS = 60_000;
    private const TAIL_CHARS = 20_000;

    public function truncate(string $text): string
    {
        if (strlen($text) <= self::MAX_CHARS) {
            return $text;
        }

        $head = substr($text, 0, self::HEAD_CHARS);
        $tail = substr($text, -self::TAIL_CHARS);

        return $head . "\n\n[... document truncated for analysis ...]\n\n" . $tail;
    }

    public function needsTruncation(string $text): bool
    {
        return strlen($text) > self::MAX_CHARS;
    }
}
