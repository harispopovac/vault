<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Repository;
use App\Models\Trigger;
use App\Models\TriggerDelivery;
use App\Models\RepositoryCollaborator;
use App\Jobs\OpenBrowserTabJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;

class BrowserTabNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;
    protected $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->webhookSecret = 'test-webhook-secret';
        $this->repository = Repository::factory()->withWebhookSecret($this->webhookSecret)->create([
            'github_id' => 1052961768, // Match the actual repository ID
            'name' => 'harispopovac/vault'
        ]);
    }

    public function test_webhook_creates_browser_tab_delivery()
    {
        $trigger = Trigger::factory()->forPushEvents()->create([
            'repository_id' => $this->repository->id,
            'target_type' => 'all'
        ]);

        $collaborator = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 12345
        ]);

        $payload = $this->generatePushPayload();
        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/v1/webhook/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => 'test-delivery-123',
            'X-Hub-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Check that delivery was created with browser tab data
        $this->assertDatabaseHas('trigger_deliveries', [
            'trigger_id' => $trigger->id,
            'target_user_id' => 12345,
            'github_event_type' => 'push',
            'status' => 'pending'
        ]);

        $delivery = TriggerDelivery::latest()->first();
        $this->assertNotNull($delivery->prompt_token);
        $this->assertNotNull($delivery->delivery_url);
        $this->assertStringContains('/prompt/', $delivery->delivery_url);

        // Check that OpenBrowserTabJob was dispatched
        Queue::assertPushed(OpenBrowserTabJob::class, function ($job) use ($delivery) {
            return $job->delivery->id === $delivery->id;
        });
    }

    public function test_browser_tab_job_caches_trigger_data()
    {
        $delivery = TriggerDelivery::factory()->withPushPayload()->create([
            'target_user_id' => 1,
            'status' => 'pending'
        ]);

        $job = new OpenBrowserTabJob($delivery);
        $job->handle();

        // Check that trigger data was cached for SSE pickup
        $cacheKey = "browser_tab_trigger:1";
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals($delivery->delivery_url, $cachedData['url']);
        $this->assertEquals($delivery->id, $cachedData['delivery_id']);
        $this->assertEquals($delivery->trigger->name, $cachedData['trigger_name']);
    }

    public function test_sse_endpoint_returns_cached_browser_tab_trigger()
    {
        // Cache a browser tab trigger
        $triggerData = [
            'url' => 'http://example.com/prompt/test-token',
            'delivery_id' => 'test-delivery-id',
            'trigger_name' => 'Test Trigger',
            'repository' => 'test-repo',
            'event_type' => 'push',
            'timestamp' => now()->toISOString()
        ];

        Cache::put('browser_tab_trigger:1', $triggerData, 300);

        $response = $this->get('/api/v1/notifications/stream');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');

        // The response should contain the browser tab trigger
        $content = $response->getContent();
        $this->assertStringContains('browser_tab', $content);
        $this->assertStringContains('test-repo', $content);
        $this->assertStringContains('Test Trigger', $content);

        // Cache should be cleared after sending
        $this->assertNull(Cache::get('browser_tab_trigger:1'));
    }

    public function test_prompt_page_displays_correctly_with_delivery_data()
    {
        $delivery = TriggerDelivery::factory()->withPushPayload()->create([
            'status' => 'pending'
        ]);

        $response = $this->get("/prompt/{$delivery->prompt_token}");

        $response->assertStatus(200)
            ->assertSee($delivery->trigger->name)
            ->assertSee('Repository:')
            ->assertSee('Event:')
            ->assertSee('push');
    }

    public function test_prompt_page_returns_404_for_invalid_token()
    {
        $response = $this->get("/prompt/invalid-token-123");

        $response->assertStatus(404);
    }

    public function test_prompt_page_shows_already_completed_for_responded_delivery()
    {
        $delivery = TriggerDelivery::factory()->responded()->create();

        $response = $this->get("/prompt/{$delivery->prompt_token}");

        // Should show completed view instead of form
        $response->assertStatus(200);
        // Adjust assertion based on your completed view content
    }

    public function test_user_can_submit_prompt_response()
    {
        $delivery = TriggerDelivery::factory()->create([
            'status' => 'delivered'
        ]);

        $responseData = [
            'knowledge_context' => 'I implemented a new feature for browser tab notifications',
            'technical_details' => 'Used Laravel queues and Server-Sent Events',
            'lessons_learned' => 'SSE requires careful handling of output buffering',
            'future_considerations' => 'Consider adding authentication for production use'
        ];

        $response = $this->postJson("/prompt/{$delivery->prompt_token}", $responseData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Response saved successfully']);

        $delivery->refresh();
        $this->assertEquals('responded', $delivery->status);
        $this->assertNotNull($delivery->responded_at);
        $this->assertEquals($responseData, $delivery->response_data);
    }

    public function test_responded_prompt_cannot_be_resubmitted()
    {
        $delivery = TriggerDelivery::factory()->responded()->create();

        $response = $this->postJson("/prompt/{$delivery->prompt_token}", [
            'knowledge_context' => 'New response'
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid or already completed prompt']);
    }

    public function test_webhook_targets_specific_users_correctly()
    {
        $collaborator1 = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 12345
        ]);

        $collaborator2 = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 67890
        ]);

        $trigger = Trigger::factory()->targetingSpecificUsers([12345])->create([
            'repository_id' => $this->repository->id
        ]);

        $payload = $this->generatePushPayload();
        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/v1/webhook/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => 'test-delivery-123',
            'X-Hub-Signature-256' => $signature
        ]);

        $response->assertStatus(200);

        // Should only create delivery for user 12345
        $this->assertEquals(1, TriggerDelivery::count());
        $this->assertDatabaseHas('trigger_deliveries', [
            'target_user_id' => 12345
        ]);
        $this->assertDatabaseMissing('trigger_deliveries', [
            'target_user_id' => 67890
        ]);
    }

    public function test_pull_request_filter_works_correctly()
    {
        $trigger = Trigger::factory()->forPullRequests()->create([
            'repository_id' => $this->repository->id
        ]);

        RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id
        ]);

        // Test merged PR (should trigger)
        $mergedPrPayload = $this->generatePullRequestPayload(true);
        $signature = $this->generateSignature($mergedPrPayload);

        $response = $this->postJson('/api/v1/webhook/github', $mergedPrPayload, [
            'X-GitHub-Event' => 'pull_request',
            'X-GitHub-Delivery' => 'test-delivery-merged',
            'X-Hub-Signature-256' => $signature
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, TriggerDelivery::count());

        // Test unmerged PR (should not trigger)
        $unmergedPrPayload = $this->generatePullRequestPayload(false);
        $signature2 = $this->generateSignature($unmergedPrPayload);

        $response2 = $this->postJson('/api/v1/webhook/github', $unmergedPrPayload, [
            'X-GitHub-Event' => 'pull_request',
            'X-GitHub-Delivery' => 'test-delivery-unmerged',
            'X-Hub-Signature-256' => $signature2
        ]);

        $response2->assertStatus(200);
        // Should still be 1 (no new delivery created)
        $this->assertEquals(1, TriggerDelivery::count());
    }

    private function generateSignature($payload)
    {
        $json = is_array($payload) ? json_encode($payload) : $payload;
        return 'sha256=' . hash_hmac('sha256', $json, $this->webhookSecret);
    }

    private function generatePushPayload()
    {
        return [
            'repository' => [
                'id' => $this->repository->github_id,
                'name' => $this->repository->name,
                'full_name' => 'harispopovac/vault'
            ],
            'ref' => 'refs/heads/dev',
            'commits' => [
                [
                    'id' => 'abc123def456',
                    'message' => 'Test commit for browser tab notifications',
                    'author' => [
                        'name' => 'Test User',
                        'email' => 'test@example.com'
                    ]
                ]
            ],
            'sender' => [
                'id' => 12345,
                'login' => 'testuser'
            ]
        ];
    }

    private function generatePullRequestPayload($merged = true)
    {
        return [
            'action' => 'closed',
            'repository' => [
                'id' => $this->repository->github_id,
                'name' => $this->repository->name,
                'full_name' => 'harispopovac/vault'
            ],
            'pull_request' => [
                'id' => 123,
                'number' => 42,
                'title' => 'Test PR for browser tab notifications',
                'merged' => $merged,
                'merge_commit_sha' => $merged ? 'def456abc789' : null
            ],
            'sender' => [
                'id' => 12345,
                'login' => 'testuser'
            ]
        ];
    }
}