<?php

namespace App\RAC\Infrastructure\Services;

use App\LLM\Domain\Entities\Embedding;
use App\LLM\Domain\Repositories\EmbeddingRepositoryInterface;
use App\LLM\Infrastructure\Services\OllamaService;
use App\RAC\Domain\Entities\RACDocument;
use Illuminate\Support\Facades\Cache;

class RACContextService
{
    public function __construct(
        private OllamaService $ollamaService,
        private EmbeddingRepositoryInterface $embeddingRepository,
        private ExternalAPIService $externalAPIService
    ) {}

    public function enrichContext(string $query, array $sources = []): array
    {
        // 1. Buscar en la base de datos local
        $localDocuments = $this->searchLocalDocuments($query);

        // 2. Buscar en APIs externas
        $externalDocuments = $this->externalAPIService->searchDocuments($query, $sources);

        // 3. Combinar y ordenar documentos por relevancia
        $allDocuments = array_merge($localDocuments, $externalDocuments);
        usort($allDocuments, fn($a, $b) => $b->getRelevance() <=> $a->getRelevance());

        return $allDocuments;
    }

    private function searchLocalDocuments(string $query): array
    {
        $cacheKey = "rac_local_search_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
            $queryEmbedding = $this->ollamaService->generateEmbedding($query);
            $similarDocuments = $this->embeddingRepository->findSimilar(
                $queryEmbedding->getVector(),
                5
            );

            return array_map(function (Embedding $embedding) {
                return new RACDocument(
                    $embedding->getText(),
                    'local_database',
                    1.0,
                    $embedding->getMetadata() ? json_decode($embedding->getMetadata(), true) : []
                );
            }, $similarDocuments);
        });
    }

    public function addDocumentToContext(RACDocument $document): void
    {
        // Crear embedding para el documento
        $embedding = $this->ollamaService->generateEmbedding($document->getContent());

        // Guardar en la base de datos
        $this->embeddingRepository->save(new Embedding(
            $embedding->getVector(),
            $document->getContent(),
            json_encode($document->getMetadata())
        ));

        // Limpiar caché de búsquedas relacionadas
        $this->clearSearchCache($document->getContent());
    }

    private function clearSearchCache(string $content): void
    {
        $words = explode(' ', strtolower($content));
        foreach ($words as $word) {
            if (strlen($word) > 3) { // Solo palabras significativas
                Cache::forget("rac_local_search_" . md5($word));
            }
        }
    }
} 