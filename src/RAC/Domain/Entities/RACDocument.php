<?php

namespace App\RAC\Domain\Entities;

class RACDocument
{
    private string $content;
    private string $source;
    private float $relevance;
    private array $metadata;

    public function __construct(
        string $content,
        string $source,
        float $relevance = 0.0,
        array $metadata = []
    ) {
        $this->content = $content;
        $this->source = $source;
        $this->relevance = $relevance;
        $this->metadata = $metadata;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getRelevance(): float
    {
        return $this->relevance;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setRelevance(float $relevance): void
    {
        $this->relevance = $relevance;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
} 