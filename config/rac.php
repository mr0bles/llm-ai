<?php

return [
    'external_apis' => [
        'wikipedia' => [
            'endpoint' => 'https://es.wikipedia.org/w/api.php',
            'default_params' => [
                'action' => 'query',
                'format' => 'json',
                'list' => 'search',
                'srlimit' => 5,
                'srprop' => 'snippet|title',
                'srsearch' => '',
            ],
            'parser' => 'Wikipedia',
        ],
        'google' => [
            'endpoint' => 'https://www.googleapis.com/customsearch/v1',
            'headers' => [
                'X-API-Key' => env('GOOGLE_API_KEY'),
            ],
            'default_params' => [
                'cx' => env('GOOGLE_SEARCH_ENGINE_ID'),
                'num' => 5,
            ],
            'parser' => 'Google',
        ],
        'confluent' => [
            'endpoint' => env('CONFLUENT_API_URL', 'https://api.confluent.io/v2'),
            'headers' => [
                'Authorization' => 'Bearer ' . env('CONFLUENT_API_KEY'),
                'Content-Type' => 'application/json',
            ],
            'parser' => 'Confluent',
        ],
        'jira' => [
            'endpoint' => env('JIRA_API_URL', 'https://your-domain.atlassian.net/rest/api/3'),
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(env('JIRA_EMAIL') . ':' . env('JIRA_API_TOKEN')),
                'Content-Type' => 'application/json',
            ],
            'parser' => 'Jira',
        ],
    ],
    'default_sources' => ['wikipedia', 'google', 'confluent', 'jira'],
    'cache_ttl' => 86400, // 24 hours in seconds
]; 