<?php

namespace Tests\Unit\LLM\Infrastructure\Repositories;

use App\LLM\Domain\Entities\Embedding;
use App\LLM\Infrastructure\Repositories\PostgresEmbeddingRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresEmbeddingRepositoryTest extends TestCase
{
    private PostgresEmbeddingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostgresEmbeddingRepository();
    }

    public function test_save_embedding(): void
    {
        $embedding = new Embedding([0.1, 0.2, 0.3], 'test text', '{"key": "value"}');
        
        $this->repository->save($embedding);

        $this->assertDatabaseHas('embeddings', [
            'text' => 'test text',
            'metadata' => '{"key": "value"}'
        ]);
    }

    public function test_find_similar_embeddings(): void
    {
        // Crear algunos embeddings de prueba
        $this->repository->save(new Embedding([0.1, 0.2, 0.3], 'text1'));
        $this->repository->save(new Embedding([0.2, 0.3, 0.4], 'text2'));
        $this->repository->save(new Embedding([0.9, 0.8, 0.7], 'text3'));

        $similar = $this->repository->findSimilar([0.1, 0.2, 0.3], 2);

        $this->assertCount(2, $similar);
        $this->assertEquals('text1', $similar[0]->getText());
        $this->assertEquals('text2', $similar[1]->getText());
    }

    public function test_find_by_text(): void
    {
        $embedding = new Embedding([0.1, 0.2, 0.3], 'test text');
        $this->repository->save($embedding);

        $found = $this->repository->findByText('test text');

        $this->assertNotNull($found);
        $this->assertEquals('test text', $found->getText());
        $this->assertEquals([0.1, 0.2, 0.3], $found->getVector());
    }

    public function test_delete_embedding(): void
    {
        $embedding = new Embedding([0.1, 0.2, 0.3], 'test text');
        $this->repository->save($embedding);

        $this->repository->delete('test text');

        $this->assertDatabaseMissing('embeddings', [
            'text' => 'test text'
        ]);
    }
} 