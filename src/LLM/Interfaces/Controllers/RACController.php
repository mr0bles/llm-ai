<?php

namespace App\LLM\Interfaces\Controllers;

use App\LLM\Infrastructure\Services\RACService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RACController
{
    public function __construct(
        private RACService $racService
    ) {}

    public function query(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string',
            'max_documents' => 'nullable|integer|min:1|max:10'
        ]);

        $context = $this->racService->processQuery(
            $request->input('query'),
            $request->input('max_documents', 3)
        );

        return response()->json([
            'query' => $context->getQuery(),
            'response' => $context->getResponse(),
            'relevant_documents' => $context->getRelevantDocuments(),
            'metadata' => $context->getMetadata()
        ]);
    }
} 