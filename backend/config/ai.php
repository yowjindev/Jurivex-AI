<?php

return [
    'driver' => env('AI_DRIVER', 'claude'),

    'claude' => [
        'api_key'    => env('CLAUDE_API_KEY'),
        'model'      => env('CLAUDE_MODEL', 'claude-sonnet-4-6'),
        'max_tokens' => (int) env('CLAUDE_MAX_TOKENS', 4096),
    ],

    'gemini' => [
        'api_key'    => env('GEMINI_API_KEY'),
        'model'      => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 4096),
    ],
];
