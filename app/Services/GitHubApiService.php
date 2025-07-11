<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubApiService
{
    private string $token;
    private string $baseUrl = 'https://api.github.com';

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getUserRepositories(string $query = '', int $perPage = 30): array
    {
        try {
            $url = $this->baseUrl . '/user/repos';
            $params = [
                'per_page' => $perPage,
                'sort' => 'updated',
                'affiliation' => 'owner,collaborator',
            ];

            if ($query) {
                // Use search API for filtering
                $url = $this->baseUrl . '/search/repositories';
                $params = [
                    'q' => $query . ' user:' . $this->getAuthenticatedUser()['login'],
                    'per_page' => $perPage,
                    'sort' => 'updated',
                ];
            }

            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($url, $params);

            if (!$response->successful()) {
                throw new \Exception('GitHub API request failed: ' . $response->body());
            }

            $data = $response->json();
            
            // Handle different response formats
            if (isset($data['items'])) {
                // Search API response
                return $data['items'];
            }
            
            // Direct repos API response
            return $data;

        } catch (\Exception $e) {
            Log::error('GitHub API getUserRepositories failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            throw $e;
        }
    }

    public function getRepository(string $fullName): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($this->baseUrl . '/repos/' . $fullName);

            if (!$response->successful()) {
                throw new \Exception('Repository not found or access denied: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('GitHub API getRepository failed', [
                'error' => $e->getMessage(),
                'repository' => $fullName,
            ]);
            throw $e;
        }
    }

    public function createWebhook(string $fullName, string $webhookUrl, string $secret, array $events): string
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->post($this->baseUrl . '/repos/' . $fullName . '/hooks', [
                    'name' => 'web',
                    'active' => true,
                    'events' => $events,
                    'config' => [
                        'url' => $webhookUrl,
                        'content_type' => 'json',
                        'secret' => $secret,
                        'insecure_ssl' => '0',
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Webhook creation failed: ' . $response->body());
            }

            $webhookData = $response->json();
            return (string) $webhookData['id'];

        } catch (\Exception $e) {
            Log::error('GitHub API createWebhook failed', [
                'error' => $e->getMessage(),
                'repository' => $fullName,
                'webhook_url' => $webhookUrl,
            ]);
            throw $e;
        }
    }

    public function deleteWebhook(string $fullName, string $webhookId): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->delete($this->baseUrl . '/repos/' . $fullName . '/hooks/' . $webhookId);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('GitHub API deleteWebhook failed', [
                'error' => $e->getMessage(),
                'repository' => $fullName,
                'webhook_id' => $webhookId,
            ]);
            throw $e;
        }
    }

    public function getWebhooks(string $fullName): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($this->baseUrl . '/repos/' . $fullName . '/hooks');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch webhooks: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('GitHub API getWebhooks failed', [
                'error' => $e->getMessage(),
                'repository' => $fullName,
            ]);
            throw $e;
        }
    }

    public function getAuthenticatedUser(): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($this->baseUrl . '/user');

            if (!$response->successful()) {
                throw new \Exception('Failed to get authenticated user: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('GitHub API getAuthenticatedUser failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getIssues(string $fullName, array $params = []): array
    {
        try {
            $defaultParams = [
                'state' => 'open',
                'per_page' => 30,
                'sort' => 'updated',
            ];

            $params = array_merge($defaultParams, $params);

            $response = Http::withToken($this->token)
                ->timeout(30)
                ->get($this->baseUrl . '/repos/' . $fullName . '/issues', $params);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch issues: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('GitHub API getIssues failed', [
                'error' => $e->getMessage(),
                'repository' => $fullName,
            ]);
            throw $e;
        }
    }

    public function validateToken(): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get($this->baseUrl . '/user');

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }

    public function getRateLimit(): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get($this->baseUrl . '/rate_limit');

            if (!$response->successful()) {
                throw new \Exception('Failed to get rate limit: ' . $response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('GitHub API getRateLimit failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}