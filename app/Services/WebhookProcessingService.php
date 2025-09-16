<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\Trigger;
use App\Models\TriggerDelivery;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Jobs\OpenBrowserTabJob;
use Exception;

class WebhookProcessingService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_RECENT_WEBHOOKS = 100;

    /**
     * Process incoming webhook request
     */
    public function processWebhook(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $signature = $request->header('X-Hub-Signature-256');
        $event = $request->header('X-GitHub-Event');
        $deliveryId = $request->header('X-GitHub-Delivery');
        $payload = $request->all();

        try {
            // Store webhook details for debugging
            $this->storeWebhookForDebugging($request, $event, $deliveryId);

            Log::info('GitHub webhook received', [
                'event' => $event,
                'delivery_id' => $deliveryId,
                'repository' => $payload['repository']['full_name'] ?? 'unknown',
                'repository_id' => $payload['repository']['id'] ?? 'unknown',
                'payload_keys' => array_keys($payload),
                'timestamp' => now()->toISOString()
            ]);

            // Verify webhook signature
            if (!$this->verifyGitHubSignature($request, $signature)) {
                $this->logWebhookEvent('signature_failed', $deliveryId, $event);
                Log::warning('Invalid GitHub webhook signature', [
                    'delivery_id' => $deliveryId,
                    'event' => $event
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Find repository
            $repository = Repository::where('github_id', $payload['repository']['id'])->first();
            if (!$repository) {
                $this->logWebhookEvent('repository_not_found', $deliveryId, $event, [
                    'github_id' => $payload['repository']['id']
                ]);
                Log::warning('Repository not found for webhook', [
                    'github_id' => $payload['repository']['id'],
                    'delivery_id' => $deliveryId
                ]);
                return response()->json(['error' => 'Repository not found'], 404);
            }

            // Find matching triggers
            $triggers = Trigger::active()
                ->forRepository($repository->id)
                ->forEvent($event)
                ->with(['prompt', 'organization'])
                ->get();

            if ($triggers->isEmpty()) {
                $this->logWebhookEvent('no_triggers_matched', $deliveryId, $event, [
                    'repository_id' => $repository->id
                ]);
                Log::info('No matching triggers found', [
                    'repository_id' => $repository->id,
                    'event' => $event,
                    'delivery_id' => $deliveryId
                ]);
                return response()->json(['message' => 'No triggers matched'], 200);
            }

            // Process triggers and create deliveries
            $result = $this->processTriggers($triggers, $payload, $event, $deliveryId, $repository);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logWebhookEvent('processed_successfully', $deliveryId, $event, [
                'triggers_matched' => $result['triggers_matched'],
                'deliveries_created' => $result['deliveries_created'],
                'processing_time_ms' => $processingTime
            ]);

            Log::info('Webhook processed successfully', [
                'delivery_id' => $deliveryId,
                'triggers_matched' => $result['triggers_matched'],
                'deliveries_created' => $result['deliveries_created'],
                'processing_time_ms' => $processingTime
            ]);

            return response()->json([
                'message' => 'Webhook processed successfully',
                'triggers_matched' => $result['triggers_matched'],
                'deliveries_created' => $result['deliveries_created'],
                'processing_time_ms' => $processingTime
            ], 200);

        } catch (Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logWebhookEvent('processing_failed', $deliveryId, $event, [
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ]);

            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'delivery_id' => $deliveryId ?? null,
                'event' => $event ?? null,
                'processing_time_ms' => $processingTime,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test webhook with simulated payload
     */
    public function testWebhook(string $event, string $repositoryId, array $payload): JsonResponse
    {
        try {
            $repository = Repository::findOrFail($repositoryId);

            // Add test metadata to payload
            $payload['test_mode'] = true;
            $payload['test_timestamp'] = now()->toISOString();

            // Simulate GitHub headers
            $deliveryId = 'test-' . uniqid();

            Log::info('Testing webhook', [
                'event' => $event,
                'repository_id' => $repositoryId,
                'delivery_id' => $deliveryId
            ]);

            // Find matching triggers
            $triggers = Trigger::active()
                ->forRepository($repository->id)
                ->forEvent($event)
                ->with(['prompt', 'organization'])
                ->get();

            if ($triggers->isEmpty()) {
                return response()->json([
                    'message' => 'No triggers would match this event',
                    'triggers_matched' => 0,
                    'deliveries_would_be_created' => 0,
                    'test_mode' => true,
                    'event' => $event,
                    'repository_id' => $repositoryId,
                    'triggers' => []
                ], 200);
            }

            // Process triggers in test mode
            $result = $this->processTriggers($triggers, $payload, $event, $deliveryId, $repository, true);

            return response()->json([
                'message' => 'Webhook test completed successfully',
                'triggers_matched' => $result['triggers_matched'],
                'deliveries_would_be_created' => $result['deliveries_created'],
                'test_mode' => true,
                'triggers' => $triggers->map(function ($trigger) {
                    return [
                        'id' => $trigger->id,
                        'name' => $trigger->name,
                        'event_type' => $trigger->event_type,
                        'target_type' => $trigger->target_type,
                        'is_active' => $trigger->is_active
                    ];
                })
            ], 200);

        } catch (Exception $e) {
            Log::error('Webhook test failed', [
                'error' => $e->getMessage(),
                'event' => $event,
                'repository_id' => $repositoryId
            ]);

            return response()->json([
                'error' => 'Webhook test failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process triggers and create deliveries
     */
    private function processTriggers(
        $triggers,
        array $payload,
        string $event,
        string $deliveryId,
        Repository $repository,
        bool $testMode = false
    ): array {
        $deliveriesCreated = 0;
        $triggersMatched = 0;

        foreach ($triggers as $trigger) {
            if ($this->shouldProcessTrigger($trigger, $payload)) {
                $triggersMatched++;
                $targetUsers = $this->getTargetUsers($trigger, $repository);

                foreach ($targetUsers as $user) {
                    if (!$testMode) {
                        // Generate unique token for this delivery
                        $token = Str::random(32);

                        $delivery = TriggerDelivery::create([
                            'trigger_id' => $trigger->id,
                            'target_user_id' => $user['id'], // Fixed: access array key instead of object property
                            'github_event_type' => $event,
                            'github_payload' => $payload,
                            'github_delivery_id' => $deliveryId,
                            'status' => 'pending',
                            'prompt_token' => $token,
                            'delivery_url' => url("/prompt/{$token}"),
                        ]);

                        // Trigger browser tab opening
                        dispatch(new OpenBrowserTabJob($delivery));

                        Log::info('Trigger delivery created and browser tab triggered', [
                            'delivery_id' => $delivery->id,
                            'trigger' => $trigger->name,
                            'user_id' => $user['id'],
                            'prompt_url' => $delivery->delivery_url
                        ]);
                    }
                    $deliveriesCreated++;
                }

                Log::debug('Trigger processed', [
                    'trigger_id' => $trigger->id,
                    'trigger_name' => $trigger->name,
                    'target_users_count' => count($targetUsers),
                    'delivery_id' => $deliveryId,
                    'test_mode' => $testMode
                ]);
            }
        }

        return [
            'triggers_matched' => $triggersMatched,
            'deliveries_created' => $deliveriesCreated
        ];
    }

    /**
     * Verify GitHub webhook signature using HMAC
     */
    private function verifyGitHubSignature(Request $request, ?string $signature): bool
    {
        if (!$signature) {
            Log::warning('Webhook signature verification failed: No signature provided');
            return false;
        }

        // Extract the repository from payload to get webhook secret
        $payload = $request->all();
        $githubRepoId = $payload['repository']['id'] ?? null;

        Log::info('Signature verification debug', [
            'github_repo_id' => $githubRepoId,
            'signature_received' => $signature
        ]);

        $repository = Repository::where('github_id', $githubRepoId)->first();

        if (!$repository) {
            Log::warning('Webhook signature verification failed: Repository not found', [
                'github_repo_id' => $githubRepoId
            ]);
            return false;
        }

        if (!$repository->webhook_secret) {
            Log::warning('Webhook signature verification failed: No webhook secret', [
                'repository_name' => $repository->name
            ]);
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $repository->webhook_secret);

        Log::info('Signature comparison', [
            'expected' => $expectedSignature,
            'received' => $signature,
            'repository' => $repository->name,
            'secret_length' => strlen($repository->webhook_secret)
        ]);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check if trigger should process based on event filters
     */
    private function shouldProcessTrigger(Trigger $trigger, array $payload): bool
    {
        $eventFilters = $trigger->event_filters;

        if (empty($eventFilters)) {
            return true;
        }

        // Apply branch filters for push events
        if ($payload['ref'] ?? null) {
            $branch = str_replace('refs/heads/', '', $payload['ref']);
            if (isset($eventFilters['branches']) && !empty($eventFilters['branches'])) {
                $allowedBranches = $eventFilters['branches'];
                if (!in_array($branch, $allowedBranches) && !in_array('*', $allowedBranches)) {
                    Log::debug('Trigger filtered out by branch', [
                        'trigger_id' => $trigger->id,
                        'branch' => $branch,
                        'allowed_branches' => $allowedBranches
                    ]);
                    return false;
                }
            }
        }

        // Apply action filters for pull request events
        if (isset($payload['action']) && isset($eventFilters['actions'])) {
            if (!in_array($payload['action'], $eventFilters['actions'])) {
                Log::debug('Trigger filtered out by action', [
                    'trigger_id' => $trigger->id,
                    'action' => $payload['action'],
                    'allowed_actions' => $eventFilters['actions']
                ]);
                return false;
            }
        }

        // Apply file pattern filters for push events
        if (isset($eventFilters['file_patterns']) && !empty($eventFilters['file_patterns'])) {
            $changedFiles = $this->extractChangedFiles($payload);
            if (!$this->matchesFilePatterns($changedFiles, $eventFilters['file_patterns'])) {
                Log::debug('Trigger filtered out by file patterns', [
                    'trigger_id' => $trigger->id,
                    'changed_files' => $changedFiles,
                    'file_patterns' => $eventFilters['file_patterns']
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Extract changed files from webhook payload
     */
    private function extractChangedFiles(array $payload): array
    {
        $files = [];

        // For push events
        if (isset($payload['commits'])) {
            foreach ($payload['commits'] as $commit) {
                $files = array_merge($files, $commit['added'] ?? []);
                $files = array_merge($files, $commit['modified'] ?? []);
                $files = array_merge($files, $commit['removed'] ?? []);
            }
        }

        // For pull request events
        if (isset($payload['pull_request']['changed_files'])) {
            // This would require additional API call to get changed files
            // For now, we'll skip file pattern matching for PRs
        }

        return array_unique($files);
    }

    /**
     * Check if changed files match any of the file patterns
     */
    private function matchesFilePatterns(array $files, array $patterns): bool
    {
        foreach ($files as $file) {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $file)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get target users for a trigger based on targeting configuration
     */
    private function getTargetUsers(Trigger $trigger, Repository $repository): array
    {
        switch ($trigger->target_type) {
            case 'all':
                return $repository->users()->get()->toArray();

            case 'specific_users':
                if (empty($trigger->target_users)) {
                    return [];
                }
                return User::whereIn('id', $trigger->target_users)->get()->toArray();

            case 'roles':
                if (empty($trigger->target_roles)) {
                    return [];
                }
                return User::whereIn('role_id', $trigger->target_roles)->get()->toArray();

            default:
                return [];
        }
    }

    /**
     * Store webhook details for debugging
     */
    private function storeWebhookForDebugging(Request $request, ?string $event, ?string $deliveryId): void
    {
        $webhookData = [
            'delivery_id' => $deliveryId,
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'headers' => [
                'X-GitHub-Event' => $request->header('X-GitHub-Event'),
                'X-GitHub-Delivery' => $request->header('X-GitHub-Delivery'),
                'X-Hub-Signature-256' => $request->header('X-Hub-Signature-256'),
                'User-Agent' => $request->header('User-Agent'),
            ],
            'payload_size' => strlen($request->getContent()),
            'repository' => $request->input('repository.full_name'),
        ];

        // Store recent webhooks
        $recentWebhooks = Cache::get('webhook:recent', []);
        array_unshift($recentWebhooks, $webhookData);

        // Keep only last N webhooks
        $recentWebhooks = array_slice($recentWebhooks, 0, self::MAX_RECENT_WEBHOOKS);

        Cache::put('webhook:recent', $recentWebhooks, self::CACHE_TTL);
    }

    /**
     * Log webhook processing events
     */
    private function logWebhookEvent(string $event, ?string $deliveryId, ?string $githubEvent, array $extra = []): void
    {
        $logEntry = [
            'event' => $event,
            'delivery_id' => $deliveryId,
            'github_event' => $githubEvent,
            'timestamp' => now()->toISOString(),
            ...$extra
        ];

        // Store webhook logs
        $logs = Cache::get('webhook:logs', []);
        array_unshift($logs, $logEntry);

        // Keep only last N logs
        $logs = array_slice($logs, 0, self::MAX_RECENT_WEBHOOKS);

        Cache::put('webhook:logs', $logs, self::CACHE_TTL);
    }
}