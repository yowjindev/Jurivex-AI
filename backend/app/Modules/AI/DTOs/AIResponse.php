<?php

namespace App\Modules\AI\DTOs;

readonly class AIResponse
{
    public function __construct(
        public string $content,
        public int    $inputTokens,
        public int    $outputTokens,
        public string $model,
    ) {}
}
