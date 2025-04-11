<?php

namespace App\LLM\Infrastructure\Repositories;

use App\LLM\Domain\Entities\Embedding;
use App\LLM\Domain\Repositories\EmbeddingRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PostgresEmbeddingRepository implements EmbeddingRepositoryInterface
{
    public function save(Embedding $embedding): void
    {
        DB::table('embeddings')->insert([
            'text' => $embedding->getText(),
            'vector' => DB::raw("'[" . implode(',', $embedding->getVector()) . "]'::vector"),
            'metadata' => $embedding->getMetadata(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function findSimilar(array $vector, int $limit = 5): array
    {
        $vectorString = '[' . implode(',', $vector) . ']';
        
        $results = DB::select("
            SELECT text, metadata, vector <-> ?::vector as distance
            FROM embeddings
            ORDER BY vector <-> ?::vector
            LIMIT ?
        ", [$vectorString, $vectorString, $limit]);

        return array_map(function ($result) {
            return new Embedding(
                json_decode($result->vector, true),
                $result->text,
                $result->metadata
            );
        }, $results);
    }

    public function findByText(string $text): ?Embedding
    {
        $result = DB::table('embeddings')
            ->where('text', $text)
            ->first();

        if (!$result) {
            return null;
        }

        return new Embedding(
            json_decode($result->vector, true),
            $result->text,
            $result->metadata
        );
    }

    public function delete(string $text): void
    {
        DB::table('embeddings')
            ->where('text', $text)
            ->delete();
    }
} 