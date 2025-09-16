<?php

namespace App\Console\Commands;

use App\Models\Repository;
use App\Models\Trigger;
use App\Services\WebhookProcessingService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestWebhookCommand extends Command
{
    protected $signature = 'webhook:test
                           {--event=push : GitHub event type to simulate}
                           {--repository= : Repository ID or GitHub repo name (owner/repo)}
                           {--payload= : Path to JSON payload file or JSON string}
                           {--list : List available test payloads}
                           {--ngrok : Test with ngrok tunnel}
                           {--debug : Enable debug output}';

    protected $description = 'Test webhook processing with simulated GitHub events';

    private WebhookProcessingService $webhookService;

    public function __construct(WebhookProcessingService $webhookService)
    {
        parent::__construct();
        $this->webhookService = $webhookService;
    }

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listTestPayloads();
        }

        if ($this->option('ngrok')) {
            return $this->testWithNgrok();
        }

        return $this->testWebhook();
    }

    /**
     * Test webhook processing with simulated payload
     */
    private function testWebhook(): int
    {
        $event = $this->option('event');
        $repositoryInput = $this->option('repository');
        $payloadInput = $this->option('payload');

        // Get repository
        $repository = $this->getRepository($repositoryInput);
        if (!$repository) {
            $this->error('Repository not found. Use --repository option with ID or owner/repo format');
            return 1;
        }

        // Get payload
        $payload = $this->getPayload($payloadInput, $event, $repository);
        if (!$payload) {
            $this->error('Invalid payload. Use --payload with JSON file path or JSON string');
            return 1;
        }

        $this->info("Testing webhook for repository: {$repository->name}");
        $this->info("Event type: {$event}");

        if ($this->option('debug')) {
            $this->line('Payload:');
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
        }

        // Test webhook processing
        try {
            $response = $this->webhookService->testWebhook($event, $repository->id, $payload);
            $data = $response->getData(true);

            $this->info('✓ Webhook test completed successfully');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Event', $event],
                    ['Repository', $repository->name],
                    ['Triggers Found', $data['triggers_matched'] ?? 0],
                    ['Deliveries Would Create', $data['deliveries_would_be_created'] ?? 0],
                    ['Test Mode', $data['test_mode'] ? 'Yes' : 'No'],
                ]
            );

            if (!empty($data['triggers'])) {
                $this->line('');
                $this->info('Matching Triggers:');
                $this->table(
                    ['ID', 'Name', 'Event Type', 'Target Type', 'Active'],
                    collect($data['triggers'])->map(function ($trigger) {
                        return [
                            $trigger['id'],
                            $trigger['name'],
                            $trigger['event_type'],
                            $trigger['target_type'],
                            $trigger['is_active'] ? 'Yes' : 'No',
                        ];
                    })->toArray()
                );
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Webhook test failed: {$e->getMessage()}");
            if ($this->option('debug')) {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Test with ngrok tunnel
     */
    private function testWithNgrok(): int
    {
        $this->info('Testing webhook with ngrok tunnel...');

        // Check if ngrok is running
        try {
            $response = Http::get('http://localhost:4040/api/tunnels');
            $tunnels = $response->json()['tunnels'] ?? [];

            if (empty($tunnels)) {
                $this->error('No ngrok tunnels found. Make sure ngrok is running with: ngrok http 8000');
                return 1;
            }

            $tunnel = collect($tunnels)->first(function ($tunnel) {
                return str_contains($tunnel['public_url'], 'https://');
            });

            if (!$tunnel) {
                $this->error('No HTTPS tunnel found. Make sure ngrok is running properly.');
                return 1;
            }

            $webhookUrl = $tunnel['public_url'] . '/api/webhooks/github';
            $this->info("Found ngrok tunnel: {$webhookUrl}");

            // Test the webhook endpoint
            $testPayload = $this->getDefaultPayload('push');
            $headers = [
                'X-GitHub-Event' => 'push',
                'X-GitHub-Delivery' => 'test-' . uniqid(),
                'X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', json_encode($testPayload), 'test-secret'),
                'Content-Type' => 'application/json',
                'User-Agent' => 'GitHub-Hookshot/webhook-test',
            ];

            $this->info('Sending test webhook...');
            $response = Http::withHeaders($headers)->post($webhookUrl, $testPayload);

            if ($response->successful()) {
                $this->info('✓ Webhook delivered successfully');
                $this->line("Response: {$response->body()}");
            } else {
                $this->error("✗ Webhook delivery failed: {$response->status()} - {$response->body()}");
                return 1;
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("ngrok test failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * List available test payloads
     */
    private function listTestPayloads(): int
    {
        $this->info('Available test event types:');

        $events = [
            'push' => 'Code pushed to repository',
            'pull_request' => 'Pull request opened/closed/merged',
            'issues' => 'Issue opened/closed/commented',
            'issue_comment' => 'Comment added to issue',
            'pull_request_review' => 'Pull request reviewed',
            'release' => 'Release published',
            'star' => 'Repository starred',
            'fork' => 'Repository forked',
        ];

        $this->table(['Event', 'Description'], collect($events)->map(function ($desc, $event) {
            return [$event, $desc];
        })->toArray());

        $this->line('');
        $this->info('Usage examples:');
        $this->line('  php artisan webhook:test --event=push --repository=1');
        $this->line('  php artisan webhook:test --event=pull_request --repository=owner/repo --payload=test-pr.json');
        $this->line('  php artisan webhook:test --ngrok');

        return 0;
    }

    /**
     * Get repository by ID or owner/repo format
     */
    private function getRepository(?string $input): ?Repository
    {
        if (!$input) {
            return null;
        }

        // Try by UUID first
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $input)) {
            return Repository::find($input);
        }

        // Try by numeric ID (for backward compatibility)
        if (is_numeric($input)) {
            return Repository::find($input);
        }

        // Try by owner/repo format
        if (str_contains($input, '/')) {
            [$owner, $repo] = explode('/', $input, 2);
            // Look up by name since repository table structure may vary
            return Repository::where('name', $input)->first();
        }

        return null;
    }

    /**
     * Get payload from input
     */
    private function getPayload(?string $input, string $event, Repository $repository): ?array
    {
        if (!$input) {
            return $this->getDefaultPayload($event, $repository);
        }

        // Try as file path
        if (file_exists($input)) {
            $content = file_get_contents($input);
            $payload = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $payload;
            }
        }

        // Try as JSON string
        $payload = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $payload;
        }

        return null;
    }

    /**
     * Get default payload for event type
     */
    private function getDefaultPayload(string $event, ?Repository $repository = null): array
    {
        $basePayload = [
            'repository' => [
                'id' => $repository?->github_id ?? 123456789,
                'name' => $repository?->name ?? 'test-repo',
                'full_name' => ($repository?->owner ?? 'test-owner') . '/' . ($repository?->name ?? 'test-repo'),
                'owner' => [
                    'login' => $repository?->owner ?? 'test-owner',
                    'id' => 987654321,
                ],
                'private' => false,
                'default_branch' => 'main',
            ],
            'sender' => [
                'login' => 'test-user',
                'id' => 123456,
            ],
        ];

        return match ($event) {
            'push' => array_merge($basePayload, [
                'ref' => 'refs/heads/main',
                'before' => '0000000000000000000000000000000000000000',
                'after' => 'abcdef1234567890abcdef1234567890abcdef12',
                'commits' => [
                    [
                        'id' => 'abcdef1234567890abcdef1234567890abcdef12',
                        'message' => 'Test commit message',
                        'author' => [
                            'name' => 'Test User',
                            'email' => 'test@example.com',
                        ],
                        'added' => ['new-file.txt'],
                        'modified' => ['existing-file.txt'],
                        'removed' => [],
                    ],
                ],
                'head_commit' => [
                    'id' => 'abcdef1234567890abcdef1234567890abcdef12',
                    'message' => 'Test commit message',
                ],
            ]),

            'pull_request' => array_merge($basePayload, [
                'action' => 'opened',
                'pull_request' => [
                    'id' => 987654321,
                    'number' => 42,
                    'title' => 'Test Pull Request',
                    'body' => 'This is a test pull request',
                    'state' => 'open',
                    'user' => [
                        'login' => 'test-contributor',
                        'id' => 111222333,
                    ],
                    'head' => [
                        'ref' => 'feature-branch',
                        'sha' => 'abcdef1234567890abcdef1234567890abcdef12',
                    ],
                    'base' => [
                        'ref' => 'main',
                        'sha' => '1234567890abcdef1234567890abcdef12345678',
                    ],
                ],
            ]),

            'issues' => array_merge($basePayload, [
                'action' => 'opened',
                'issue' => [
                    'id' => 555666777,
                    'number' => 123,
                    'title' => 'Test Issue',
                    'body' => 'This is a test issue',
                    'state' => 'open',
                    'user' => [
                        'login' => 'issue-reporter',
                        'id' => 444555666,
                    ],
                ],
            ]),

            default => $basePayload,
        };
    }
}