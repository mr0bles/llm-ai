<?php

namespace Tests\Unit\LLM\Infrastructure\Services;

use App\LLM\Infrastructure\Services\OllamaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaServiceTest extends TestCase
{
    private OllamaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OllamaService();
    }

    public function test_generate_embedding(): void
    {
        Http::fake([
            'ollama:11434/api/embeddings' => Http::response([
                'embedding' => [0.1, 0.2, 0.3]
            ], 200)
        ]);

        $embedding = $this->service->generateEmbedding('test text');

        $this->assertEquals([0.1, 0.2, 0.3], $embedding->getVector());
        $this->assertEquals('test text', $embedding->getText());
    }

    public function test_generate_completion(): void
    {
        Http::fake([
            'ollama:11434/api/generate' => Http::response([
                'response' => 'test response'
            ], 200)
        ]);

        $response = $this->service->generateCompletion('test prompt');

        $this->assertEquals('test response', $response);
    }

    public function test_generate_embedding_fails(): void
    {
        Http::fake([
            'ollama:11434/api/embeddings' => Http::response([], 500)
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->generateEmbedding('test text');
    }
} 