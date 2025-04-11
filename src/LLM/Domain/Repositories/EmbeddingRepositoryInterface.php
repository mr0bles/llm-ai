<?php

namespace App\LLM\Domain\Repositories;

use App\LLM\Domain\Entities\Embedding;

interface EmbeddingRepositoryInterface
{
    public function save(Embedding $embedding): void;
    public function findSimilar(array $vector, int $limit = 5): array;
    public function findByText(string $text): ?Embedding;
    public function delete(string $text): void;
} 