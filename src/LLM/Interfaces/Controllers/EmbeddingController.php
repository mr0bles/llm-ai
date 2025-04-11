<?php

namespace App\LLM\Interfaces\Controllers;

use App\LLM\Domain\Repositories\EmbeddingRepositoryInterface;
use App\LLM\Infrastructure\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmbeddingController
{
    public function __construct(
        private EmbeddingRepositoryInterface $embeddingRepository,
        private OllamaService $ollamaService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'metadata' => 'nullable|json'
        ]);

        $embedding = $this->ollamaService->generateEmbedding($request->text);
        $this->embeddingRepository->save($embedding);

        return response()->json([
            'message' => 'Embedding created successfully',
            'embedding' => [
                'text' => $embedding->getText(),
                'metadata' => $embedding->getMetadata()
            ]
        ], 201);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        $embedding = $this->ollamaService->generateEmbedding($request->text);
        $similar = $this->embeddingRepository->findSimilar(
            $embedding->getVector(),
            $request->input('limit', 5)
        );

        return response()->json([
            'results' => array_map(function ($embedding) {
                return [
                    'text' => $embedding->getText(),
                    'metadata' => $embedding->getMetadata()
                ];
            }, $similar)
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        $this->embeddingRepository->delete($request->text);

        return response()->json([
            'message' => 'Embedding deleted successfully'
        ]);
    }
} 