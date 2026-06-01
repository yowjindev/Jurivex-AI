<?php

namespace App\Modules\AI\Services;

use App\Exceptions\AI\EmbeddingException;
use App\Modules\AI\Contracts\EmbeddingClientContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiEmbeddingClient implements EmbeddingClientContract
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-embedding-001',
    ) {}

    public function embed(string $text): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:embedContent";

        try {
            $response = Http::withQueryParameters(['key' => $this->apiKey])
                ->post($url, [
                    'model'   => "models/{$this->model}",
                    'content' => ['parts' => [['text' => $text]]],
                ]);
        } catch (ConnectionException $e) {
            throw new EmbeddingException('Gemini embedding connection failed: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw new EmbeddingException(
                'Gemini embedding request failed (' . $response->status() . '): ' . $response->body()
            );
        }

        $values = $response->json('embedding.values');

        if (! is_array($values) || count($values) !== $this->getDimensions()) {
            throw new EmbeddingException(
                'Gemini embedding returned unexpected dimension count: ' . count((array) $values)
            );
        }

        return $values;
    }

    public function getDimensions(): int
    {
        return (int) config('ai.embedding.gemini.dimensions', 3072);
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
