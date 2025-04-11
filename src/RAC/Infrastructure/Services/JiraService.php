<?php

namespace App\RAC\Infrastructure\Services;

use App\RAC\Domain\Entities\RACDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class JiraService
{
    private string $apiUrl;
    private string $username;
    private string $apiToken;
    private array $headers;

    public function __construct()
    {
        $this->apiUrl = env('JIRA_API_URL', 'https://your-domain.atlassian.net/rest/api/3');
        $this->username = env('JIRA_USERNAME', '');
        $this->apiToken = env('JIRA_API_TOKEN', '');
        $this->headers = [
            'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->apiToken),
            'Content-Type' => 'application/json',
        ];
    }

    public function searchIssues(string $query, int $limit = 5): array
    {
        $cacheKey = "jira_issues_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $limit) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->get("{$this->apiUrl}/search", [
                        'jql' => "text ~ \"{$query}\" ORDER BY updated DESC",
                        'maxResults' => $limit,
                        'fields' => 'summary,description,status,assignee,created,updated,priority,project'
                    ]);

                if (!$response->successful()) {
                    return [];
                }

                $issues = $response->json()['issues'] ?? [];
                return array_map(function ($issue) {
                    $fields = $issue['fields'];
                    return new RACDocument(
                        $fields['description'] ?? $fields['summary'] ?? '',
                        'jira',
                        1.0,
                        [
                            'summary' => $fields['summary'] ?? '',
                            'status' => $fields['status']['name'] ?? '',
                            'assignee' => $fields['assignee']['displayName'] ?? '',
                            'created' => $fields['created'] ?? '',
                            'updated' => $fields['updated'] ?? '',
                            'priority' => $fields['priority']['name'] ?? '',
                            'project' => $fields['project']['name'] ?? '',
                            'url' => "{$this->apiUrl}/browse/{$issue['key']}"
                        ]
                    );
                }, $issues);
            } catch (\Exception $e) {
                \Log::error("Error searching Jira issues: " . $e->getMessage());
                return [];
            }
        });
    }

    public function searchProjects(string $query, int $limit = 5): array
    {
        $cacheKey = "jira_projects_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $limit) {
            try {
                $response = Http::withHeaders($this->headers)
                    ->get("{$this->apiUrl}/project/search", [
                        'query' => $query,
                        'maxResults' => $limit
                    ]);

                if (!$response->successful()) {
                    return [];
                }

                $projects = $response->json()['values'] ?? [];
                return array_map(function ($project) {
                    return new RACDocument(
                        $project['description'] ?? $project['name'] ?? '',
                        'jira_project',
                        1.0,
                        [
                            'name' => $project['name'] ?? '',
                            'key' => $project['key'] ?? '',
                            'url' => "{$this->apiUrl}/project/{$project['key']}",
                            'projectTypeKey' => $project['projectTypeKey'] ?? '',
                            'simplified' => $project['simplified'] ?? false,
                            'style' => $project['style'] ?? '',
                            'isPrivate' => $project['isPrivate'] ?? false
                        ]
                    );
                }, $projects);
            } catch (\Exception $e) {
                \Log::error("Error searching Jira projects: " . $e->getMessage());
                return [];
            }
        });
    }

    public function searchAll(string $query, int $limit = 5): array
    {
        $issues = $this->searchIssues($query, $limit);
        $projects = $this->searchProjects($query, $limit);
        
        return array_merge($issues, $projects);
    }
} 