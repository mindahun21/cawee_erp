<?php

namespace App\Services\AI\VectorStore;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $provider;
    protected string $model;
    protected int $dimensions;
    protected string $apiKey;

    public function __construct()
    {
        $this->provider = config('services.embedding.provider', env('EMBEDDING_PROVIDER', 'gemini'));
        $this->model = config('services.embedding.model', env('EMBEDDING_MODEL', 'text-embedding-004'));
        $this->dimensions = (int) config('services.embedding.dimensions', env('EMBEDDING_DIMENSIONS', 768));
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    /**
     * Generate an embedding vector for a given text.
     *
     * @param string $text The text to embed.
     * @return array The embedding vector (array of floats).
     */
    public function embed(string $text): array
    {
        return match ($this->provider) {
            'gemini' => $this->embedWithGemini($text),
            default => throw new \RuntimeException("Unsupported embedding provider: {$this->provider}"),
        };
    }

    /**
     * Generate embeddings for multiple texts in batch.
     *
     * @param array $texts Array of strings to embed.
     * @return array Array of embedding vectors.
     */
    public function embedBatch(array $texts): array
    {
        return match ($this->provider) {
            'gemini' => $this->embedBatchWithGemini($texts),
            default => throw new \RuntimeException("Unsupported embedding provider: {$this->provider}"),
        };
    }

    protected function embedWithGemini(string $text): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:embedContent?key={$this->apiKey}";

        $response = Http::timeout(30)->post($url, [
            'model' => "models/{$this->model}",
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
            'outputDimensionality' => $this->dimensions,
        ]);

        if ($response->failed()) {
            Log::error('EmbeddingService: Gemini API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to generate embedding: ' . $response->body());
        }

        return $response->json('embedding.values', []);
    }

    protected function embedBatchWithGemini(array $texts): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:batchEmbedContents?key={$this->apiKey}";

        $requests = array_map(fn(string $text) => [
            'model' => "models/{$this->model}",
            'content' => [
                'parts' => [['text' => $text]],
            ],
            'outputDimensionality' => $this->dimensions,
        ], $texts);

        $response = Http::timeout(60)->post($url, [
            'requests' => $requests,
        ]);

        if ($response->failed()) {
            Log::error('EmbeddingService: Gemini batch API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to generate batch embeddings: ' . $response->body());
        }

        $embeddings = $response->json('embeddings', []);

        return array_map(fn(array $e) => $e['values'] ?? [], $embeddings);
    }
}
