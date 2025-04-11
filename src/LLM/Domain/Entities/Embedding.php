<?php

namespace App\LLM\Domain\Entities;

class Embedding
{
    private array $vector;
    private string $text;
    private ?string $metadata;

    public function __construct(array $vector, string $text, ?string $metadata = null)
    {
        $this->vector = $vector;
        $this->text = $text;
        $this->metadata = $metadata;
    }

    public function getVector(): array
    {
        return $this->vector;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }
} 