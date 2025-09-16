<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\Trigger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GitHubService
{
    private string $baseUrl = 'https://api.github.com';
    private string $token;

    public function __construct()
    {
        $this->token = config('services.github.token');
    }

    /**
     * Create a webhook for a repository
     */
    public function createWebhook(Repository $repository, Trigger $trigger): ?array
    {
        try {
            $webhookUrl = config('app.url') . '/api/v1/webhook/github';
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->post("{$this->baseUrl}/repos/{$repository->owner_id}/{$repository->name}/hooks", [
                'name' => 'web',
                'active' => true,
                'events' => $trigger->github_events,
                'config' => [
                    'url' => $webhookUrl,
                    'content_type' => 'json',
                    'secret' => $trigger->webhook_secret,
                    'insecure_ssl' => '0',
                ]
            ]);

            if ($response->successful()) {
                $webhookData = $response->json();
                
                Log::info('GitHub webhook created successfully', [
                    'webhook_id' => $webhookData['id'],
                    'repository' => $repository->name,
                    'trigger_id' => $trigger->id,
                ]);

                return $webhookData;
            }

            Log::error('Failed to create GitHub webhook', [
                'status' => $response->status(),
                'response' => $response->body(),
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception creating GitHub webhook', [
                'error' => $e->getMessage(),
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);
            
            return null;
        }
    }

    /**
     * Update a webhook for a repository
     */
    public function updateWebhook(Repository $repository, Trigger $trigger): ?array
    {
        try {
            if (!$trigger->github_webhook_id) {
                return $this->createWebhook($repository, $trigger);
            }

            $webhookUrl = config('app.url') . '/api/v1/webhook/github';
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->patch("{$this->baseUrl}/repos/{$repository->owner_id}/{$repository->name}/hooks/{$trigger->github_webhook_id}", [
                'active' => $trigger->webhook_active,
                'events' => $trigger->github_events,
                'config' => [
                    'url' => $webhookUrl,
                    'content_type' => 'json',
                    'secret' => $trigger->webhook_secret,
                    'insecure_ssl' => '0',
                ]
            ]);

            if ($response->successful()) {
                $webhookData = $response->json();
                
                Log::info('GitHub webhook updated successfully', [
                    'webhook_id' => $webhookData['id'],
                    'repository' => $repository->name,
                    'trigger_id' => $trigger->id,
                ]);

                return $webhookData;
            }

            Log::error('Failed to update GitHub webhook', [
                'status' => $response->status(),
                'response' => $response->body(),
                'webhook_id' => $trigger->github_webhook_id,
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception updating GitHub webhook', [
                'error' => $e->getMessage(),
                'webhook_id' => $trigger->github_webhook_id,
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);
            
            return null;
        }
    }

    /**
     * Delete a webhook from a repository
     */
    public function deleteWebhook(Repository $repository, Trigger $trigger): bool
    {
        try {
            if (!$trigger->github_webhook_id) {
                return true;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->delete("{$this->baseUrl}/repos/{$repository->owner_id}/{$repository->name}/hooks/{$trigger->github_webhook_id}");

            if ($response->successful()) {
                Log::info('GitHub webhook deleted successfully', [
                    'webhook_id' => $trigger->github_webhook_id,
                    'repository' => $repository->name,
                    'trigger_id' => $trigger->id,
                ]);

                return true;
            }

            Log::error('Failed to delete GitHub webhook', [
                'status' => $response->status(),
                'response' => $response->body(),
                'webhook_id' => $trigger->github_webhook_id,
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Exception deleting GitHub webhook', [
                'error' => $e->getMessage(),
                'webhook_id' => $trigger->github_webhook_id,
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);
            
            return false;
        }
    }

    /**
     * List all webhooks for a repository
     */
    public function listWebhooks(Repository $repository): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->get("{$this->baseUrl}/repos/{$repository->owner_id}/{$repository->name}/hooks");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to list GitHub webhooks', [
                'status' => $response->status(),
                'response' => $response->body(),
                'repository' => $repository->name,
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Exception listing GitHub webhooks', [
                'error' => $e->getMessage(),
                'repository' => $repository->name,
            ]);
            
            return null;
        }
    }

    /**
     * Test a webhook by pinging it
     */
    public function pingWebhook(Repository $repository, Trigger $trigger): bool
    {
        try {
            if (!$trigger->github_webhook_id) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->post("{$this->baseUrl}/repos/{$repository->owner_id}/{$repository->name}/hooks/{$trigger->github_webhook_id}/pings");

            if ($response->successful()) {
                Log::info('GitHub webhook ping successful', [
                    'webhook_id' => $trigger->github_webhook_id,
                    'repository' => $repository->name,
                    'trigger_id' => $trigger->id,
                ]);

                return true;
            }

            Log::error('Failed to ping GitHub webhook', [
                'status' => $response->status(),
                'response' => $response->body(),
                'webhook_id' => $trigger->github_webhook_id,
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);

            return false;
        } catch (Exception $e) {
            Log::error('Exception pinging GitHub webhook', [
                'error' => $e->getMessage(),
                'webhook_id' => $trigger->github_webhook_id,
                'repository' => $repository->name,
                'trigger_id' => $trigger->id,
            ]);
            
            return false;
        }
    }

    /**
     * Get repository information from GitHub
     */
    public function getRepository(string $owner, string $repo): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->get("{$this->baseUrl}/repos/{$owner}/{$repo}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Exception $e) {
            Log::error('Exception getting GitHub repository', [
                'error' => $e->getMessage(),
                'owner' => $owner,
                'repo' => $repo,
            ]);
            
            return null;
        }
    }

    /**
     * Verify GitHub token has necessary permissions
     */
    public function verifyToken(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])->get("{$this->baseUrl}/user");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Exception verifying GitHub token', [
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
}