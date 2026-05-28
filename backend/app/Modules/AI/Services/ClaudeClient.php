<?php

namespace App\Modules\AI\Services;

use App\Exceptions\AI\AIProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ClaudeClient
{
    public function __construct(
        private string $apiKey,
        private string $model,
        private int    $maxTokens = 4096,
    ) {}

    public function complete(string $prompt): ClaudeResponse
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } catch (ConnectionException $e) {
            throw new AIProviderException('Claude API connection failed: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw new AIProviderException('Claude API request failed (' . $response->status() . '): ' . $response->body());
        }

        $data = $response->json();

        return new ClaudeResponse(
            content:      $data['content'][0]['text'],
            inputTokens:  $data['usage']['input_tokens'],
            outputTokens: $data['usage']['output_tokens'],
            model:        $data['model'],
        );
    }
}
