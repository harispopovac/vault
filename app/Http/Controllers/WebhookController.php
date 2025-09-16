<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\Trigger;
use App\Models\TriggerDelivery;
use App\Models\User;
use App\Services\WebhookProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class WebhookController extends Controller
{
    protected WebhookProcessingService $webhookService;

    public function __construct(WebhookProcessingService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming GitHub webhook events
     */
    public function github(Request $request): JsonResponse
    {
        return $this->webhookService->processWebhook($request);
    }

    /**
     * Verify GitHub webhook signature using HMAC
     */
    private function verifyGitHubSignature(Request $request, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        // Extract the repository from payload to get webhook secret
        $payload = $request->all();
        $repository = Repository::where('github_id', $payload['repository']['id'])->first();
        
        if (!$repository) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $repository->webhook_secret);
        
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
                    return false;
                }
            }
        }

        // Apply action filters for pull request events
        if (isset($payload['action']) && isset($eventFilters['actions'])) {
            if (!in_array($payload['action'], $eventFilters['actions'])) {
                return false;
            }
        }

        return true;
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
     * Test webhook endpoint with payload
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'event' => 'required|string',
            'repository_id' => 'required|exists:repositories,id',
            'payload' => 'required|array',
        ]);

        return $this->webhookService->testWebhook(
            $request->input('event'),
            $request->input('repository_id'),
            $request->input('payload')
        );
    }

    /**
     * Get webhook processing status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'webhook_processing_enabled' => true,
            'recent_webhooks' => Cache::get('webhook:recent', []),
            'processing_queue_size' => TriggerDelivery::where('status', 'pending')->count(),
            'failed_deliveries_count' => TriggerDelivery::where('status', 'failed')->count(),
        ]);
    }

    /**
     * Get webhook logs for debugging
     */
    public function logs(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);

        return response()->json([
            'logs' => Cache::get('webhook:logs', []),
            'recent_deliveries' => TriggerDelivery::with(['trigger', 'targetUser'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(),
        ]);
    }

    /**
     * Clear webhook cache and logs
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('webhook:recent');
        Cache::forget('webhook:logs');

        return response()->json(['message' => 'Webhook cache cleared']);
    }
}
