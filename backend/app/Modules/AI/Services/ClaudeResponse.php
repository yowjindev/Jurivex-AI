<?php

namespace App\Modules\AI\Services;

readonly class ClaudeResponse
{
    public function __construct(
        public string $content,
        public int    $inputTokens,
        public int    $outputTokens,
        public string $model,
    ) {}
}
