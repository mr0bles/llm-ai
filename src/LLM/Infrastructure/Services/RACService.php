<?php

namespace App\LLM\Infrastructure\Services;

use App\LLM\Domain\Entities\RACContext;
use App\LLM\Domain\Repositories\EmbeddingRepositoryInterface;

class RACService
{
    public function __construct(
        private OllamaService $ollamaService,
        private EmbeddingRepositoryInterface $embeddingRepository
    ) {}

    public function processQuery(string $query, int $maxDocuments = 3): RACContext
    {
        // Crear el contexto RAC
        $context = new RACContext($query);

        // Generar embedding para la consulta
        $queryEmbedding = $this->ollamaService->generateEmbedding($query);

        // Buscar documentos relevantes
        $relevantDocuments = $this->embeddingRepository->findSimilar(
            $queryEmbedding->getVector(),
            $maxDocuments
        );

        // Agregar documentos relevantes al contexto
        foreach ($relevantDocuments as $document) {
            $context->addRelevantDocument($document->getText());
        }

        // Construir el prompt con el contexto
        $prompt = $this->buildPrompt($context);

        // Generar la respuesta
        $response = $this->ollamaService->generateCompletion($prompt);
        $context->setResponse($response);

        return $context;
    }

    private function buildPrompt(RACContext $context): string
    {
        $documents = implode("\n\n", $context->getRelevantDocuments());
        
        return <<<PROMPT
        Basándote en el siguiente contexto, responde a la pregunta del usuario.
        Si no puedes encontrar la respuesta en el contexto, indícalo claramente.

        Contexto:
        {$documents}

        Pregunta del usuario:
        {$context->getQuery()}

        Respuesta:
        PROMPT;
    }
} 