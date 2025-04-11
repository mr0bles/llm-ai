<?php

namespace App\RAC\Interfaces\Controllers;

use App\LLM\Infrastructure\Services\RACService;
use App\RAC\Domain\Entities\RACDocument;
use App\RAC\Infrastructure\Services\RACContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RACController
{
    public function __construct(
        private RACService $racService,
        private RACContextService $contextService
    ) {}

    public function query(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
            'max_documents' => 'nullable|integer|min:1|max:10',
            'sources' => 'nullable|array'
        ]);

        // Enriquecer el contexto con documentos relevantes
        $documents = $this->contextService->enrichContext(
            $request->input('query'),
            $request->input('sources', config('rac.default_sources'))
        );

        // Procesar la consulta con el contexto enriquecido
        $context = $this->racService->processQuery(
            $request->input('query'),
            $request->input('max_documents', 3)
        );

        return response()->json([
            'query' => $context->getQuery(),
            'response' => $context->getResponse(),
            'documents' => array_map(function (RACDocument $doc) {
                return [
                    'content' => $doc->getContent(),
                    'source' => $doc->getSource(),
                    'relevance' => $doc->getRelevance(),
                    'metadata' => $doc->getMetadata()
                ];
            }, $documents)
        ]);
    }

    public function addDocument(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'source' => 'required|string',
            'metadata' => 'nullable|array'
        ]);

        $document = new RACDocument(
            $request->input('content'),
            $request->input('source'),
            1.0,
            $request->input('metadata', [])
        );

        $this->contextService->addDocumentToContext($document);

        return response()->json([
            'message' => 'Document added successfully',
            'document' => [
                'content' => $document->getContent(),
                'source' => $document->getSource(),
                'metadata' => $document->getMetadata()
            ]
        ], 201);
    }
} 