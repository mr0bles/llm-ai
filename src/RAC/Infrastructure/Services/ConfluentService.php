<?php

namespace App\RAC\Infrastructure\Services;

use App\RAC\Domain\Entities\RACDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ConfluentService
{
    private string $apiUrl;
    private string $apiKey;
    private array $headers;

    public function __construct()
    {
        $this->apiUrl = env('CONFLUENT_API_URL', 'https://api.confluent.io/v2');
        $this->apiKey = env('CONFLUENT_API_KEY', '');
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function searchArticles(string $query, int $limit = 5): array
    {
        $cacheKey = "confluent_articles_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $limit) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->get("{$this->apiUrl}/articles/search", [
                        'q' => $query,
                        'limit' => $limit
                    ]);

                if (!$response->successful()) {
                    return [];
                }

                $articles = $response->json()['articles'] ?? [];
                return array_map(function ($article) {
                    return new RACDocument(
                        $article['content'] ?? $article['summary'] ?? '',
                        'confluent',
                        1.0,
                        [
                            'title' => $article['title'] ?? '',
                            'url' => $article['url'] ?? '',
                            'author' => $article['author'] ?? '',
                            'published_date' => $article['published_date'] ?? '',
                            'category' => $article['category'] ?? ''
                        ]
                    );
                }, $articles);
            } catch (\Exception $e) {
                \Log::error("Error searching Confluent articles: " . $e->getMessage());
                return [];
            }
        });
    }

    public function searchDocumentation(string $query, int $limit = 5): array
    {
        $cacheKey = "confluent_docs_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $limit) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->get("{$this->apiUrl}/docs/search", [
                        'q' => $query,
                        'limit' => $limit
                    ]);

                if (!$response->successful()) {
                    return [];
                }

                $docs = $response->json()['docs'] ?? [];
                return array_map(function ($doc) {
                    return new RACDocument(
                        $doc['content'] ?? $doc['description'] ?? '',
                        'confluent_docs',
                        1.0,
                        [
                            'title' => $doc['title'] ?? '',
                            'url' => $doc['url'] ?? '',
                            'version' => $doc['version'] ?? '',
                            'product' => $doc['product'] ?? ''
                        ]
                    );
                }, $docs);
            } catch (\Exception $e) {
                \Log::error("Error searching Confluent documentation: " . $e->getMessage());
                return [];
            }
        });
    }

    public function searchAll(string $query, int $limit = 5): array
    {
        $articles = $this->searchArticles($query, $limit);
        $docs = $this->searchDocumentation($query, $limit);
        
        return array_merge($articles, $docs);
    }
} 