<?php

namespace App\LLM\Infrastructure\Services;

use Illuminate\Support\Facades\Http;
use App\LLM\Domain\Entities\Embedding;

class OllamaService
{
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->baseUrl = sprintf(
            'http://%s:%s',
            config('services.ollama.host'),
            config('services.ollama.port')
        );
        $this->model = config('services.ollama.model');
    }

    public function generateEmbedding(string $text): Embedding
    {
        $response = Http::post("{$this->baseUrl}/api/embeddings", [
            'model' => $this->model,
            'prompt' => $text
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to generate embedding: ' . $response->body());
        }

        $data = $response->json();
        return new Embedding($data['embedding'], $text);
    }

    public function generateCompletion(string $prompt, array $options = []): string
    {
        $response = Http::post("{$this->baseUrl}/api/generate", array_merge([
            'model' => $this->model,
            'prompt' => $prompt
        ], $options));

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to generate completion: ' . $response->body());
        }

        return $response->json()['response'];
    }
} 