<?php

namespace App\Modules\Documents\DTOs;

readonly class ChatResponse
{
    public function __construct(
        public string $content,
        public array  $citedChunks,        // [{chunk_id, excerpt, chunk_index, score}]
        public int    $promptTokens,
        public int    $completionTokens,
        public string $model,
        public string $conversationId,
        public string $messageId,
    ) {}
}
