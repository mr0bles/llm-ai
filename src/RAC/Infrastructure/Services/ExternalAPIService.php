<?php

namespace App\RAC\Infrastructure\Services;

use App\RAC\Domain\Entities\RACDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExternalAPIService
{
    private array $apiConfigs;

    public function __construct()
    {
        $this->apiConfigs = config('rac.external_apis', []);
    }

    public function searchDocuments(string $query, array $sources = []): array
    {
        $documents = [];
        $sources = empty($sources) ? array_keys($this->apiConfigs) : $sources;

        foreach ($sources as $source) {
            if (!isset($this->apiConfigs[$source])) {
                continue;
            }

            $config = $this->apiConfigs[$source];
            $cacheKey = "rac_search_{$source}_" . md5($query);

            // Intentar obtener resultados del caché
            if ($cached = Cache::get($cacheKey)) {
                $documents = array_merge($documents, $cached);
                continue;
            }

            try {
                $response = Http::withHeaders($config['headers'] ?? [])
                    ->get($config['endpoint'], array_merge(
                        $config['default_params'] ?? [],
                        ['query' => $query]
                    ));

                if ($response->successful()) {
                    $results = $this->parseResponse($source, $response->json());
                    Cache::put($cacheKey, $results, now()->addHours(24));
                    $documents = array_merge($documents, $results);
                }
            } catch (\Exception $e) {
                // Log error
                \Log::error("Error searching in {$source}: " . $e->getMessage());
            }
        }

        return $documents;
    }

    private function parseResponse(string $source, array $response): array
    {
        $documents = [];
        $parser = $this->apiConfigs[$source]['parser'] ?? null;

        if ($parser && method_exists($this, "parse{$parser}Response")) {
            $documents = $this->{"parse{$parser}Response"}($response);
        }

        return $documents;
    }

    private function parseWikipediaResponse(array $response): array
    {
        $documents = [];
        foreach ($response['query']['search'] ?? [] as $result) {
            $documents[] = new RACDocument(
                strip_tags($result['snippet']),
                'wikipedia',
                1.0,
                [
                    'title' => $result['title'],
                    'pageid' => $result['pageid']
                ]
            );
        }
        return $documents;
    }

    private function parseGoogleResponse(array $response): array
    {
        $documents = [];
        foreach ($response['items'] ?? [] as $result) {
            $documents[] = new RACDocument(
                $result['snippet'],
                'google',
                1.0,
                [
                    'title' => $result['title'],
                    'link' => $result['link']
                ]
            );
        }
        return $documents;
    }

    private function parseConfluentResponse(array $response): array
    {
        $documents = [];
        
        // Buscar en artículos de Confluent
        foreach ($response['articles'] ?? [] as $article) {
            $documents[] = new RACDocument(
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
        }
        
        // Buscar en documentación técnica
        foreach ($response['docs'] ?? [] as $doc) {
            $documents[] = new RACDocument(
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
        }
        
        return $documents;
    }

    private function parseJiraResponse(array $response): array
    {
        $documents = [];
        
        // Buscar en issues de Jira
        foreach ($response['issues'] ?? [] as $issue) {
            $content = $issue['fields']['description'] ?? $issue['fields']['summary'] ?? '';
            
            $documents[] = new RACDocument(
                $content,
                'jira',
                1.0,
                [
                    'key' => $issue['key'] ?? '',
                    'summary' => $issue['fields']['summary'] ?? '',
                    'status' => $issue['fields']['status']['name'] ?? '',
                    'assignee' => $issue['fields']['assignee']['displayName'] ?? '',
                    'created' => $issue['fields']['created'] ?? '',
                    'updated' => $issue['fields']['updated'] ?? '',
                    'priority' => $issue['fields']['priority']['name'] ?? '',
                    'project' => $issue['fields']['project']['name'] ?? ''
                ]
            );
        }
        
        return $documents;
    }
} 