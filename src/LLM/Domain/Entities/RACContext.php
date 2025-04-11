<?php

namespace App\LLM\Domain\Entities;

class RACContext
{
    private array $relevantDocuments;
    private string $query;
    private string $response;
    private array $metadata;

    public function __construct(
        string $query,
        array $relevantDocuments = [],
        string $response = '',
        array $metadata = []
    ) {
        $this->query = $query;
        $this->relevantDocuments = $relevantDocuments;
        $this->response = $response;
        $this->metadata = $metadata;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getRelevantDocuments(): array
    {
        return $this->relevantDocuments;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    public function addRelevantDocument(string $document): void
    {
        $this->relevantDocuments[] = $document;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
} 