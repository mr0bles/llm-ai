<?php

namespace Tests\Unit\LLM\Infrastructure\Services;

use App\LLM\Domain\Entities\Embedding;
use App\LLM\Domain\Repositories\EmbeddingRepositoryInterface;
use App\LLM\Infrastructure\Services\OllamaService;
use App\LLM\Infrastructure\Services\RACService;
use Tests\TestCase;

class RACServiceTest extends TestCase
{
    private RACService $service;
    private $ollamaServiceMock;
    private $embeddingRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ollamaServiceMock = $this->createMock(OllamaService::class);
        $this->embeddingRepositoryMock = $this->createMock(EmbeddingRepositoryInterface::class);

        $this->service = new RACService(
            $this->ollamaServiceMock,
            $this->embeddingRepositoryMock
        );
    }

    public function test_process_query(): void
    {
        $query = "¿Cuál es la capital de Francia?";
        $embedding = new Embedding([0.1, 0.2, 0.3], "Paris es la capital de Francia");
        $relevantDocuments = [$embedding];
        $expectedResponse = "Paris es la capital de Francia";

        // Configurar mocks
        $this->ollamaServiceMock->expects($this->once())
            ->method('generateEmbedding')
            ->with($query)
            ->willReturn($embedding);

        $this->embeddingRepositoryMock->expects($this->once())
            ->method('findSimilar')
            ->with($embedding->getVector(), 3)
            ->willReturn($relevantDocuments);

        $this->ollamaServiceMock->expects($this->once())
            ->method('generateCompletion')
            ->willReturn($expectedResponse);

        // Ejecutar el servicio
        $context = $this->service->processQuery($query);

        // Verificar resultados
        $this->assertEquals($query, $context->getQuery());
        $this->assertEquals($expectedResponse, $context->getResponse());
        $this->assertCount(1, $context->getRelevantDocuments());
        $this->assertEquals("Paris es la capital de Francia", $context->getRelevantDocuments()[0]);
    }

    public function test_process_query_with_no_relevant_documents(): void
    {
        $query = "¿Cuál es la capital de Francia?";
        $embedding = new Embedding([0.1, 0.2, 0.3], $query);
        $relevantDocuments = [];
        $expectedResponse = "No encontré información relevante en el contexto proporcionado.";

        // Configurar mocks
        $this->ollamaServiceMock->expects($this->once())
            ->method('generateEmbedding')
            ->with($query)
            ->willReturn($embedding);

        $this->embeddingRepositoryMock->expects($this->once())
            ->method('findSimilar')
            ->with($embedding->getVector(), 3)
            ->willReturn($relevantDocuments);

        $this->ollamaServiceMock->expects($this->once())
            ->method('generateCompletion')
            ->willReturn($expectedResponse);

        // Ejecutar el servicio
        $context = $this->service->processQuery($query);

        // Verificar resultados
        $this->assertEquals($query, $context->getQuery());
        $this->assertEquals($expectedResponse, $context->getResponse());
        $this->assertEmpty($context->getRelevantDocuments());
    }
} 