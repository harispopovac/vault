<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WebhookProcessingService;
use App\Models\Repository;
use App\Models\Trigger;
use App\Models\RepositoryCollaborator;
use App\Models\Role;
use App\Jobs\OpenBrowserTabJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class WebhookProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookProcessingService();
        $this->repository = Repository::factory()->withWebhookSecret('test-secret')->create();
        Queue::fake();
    }

    public function test_validates_webhook_signature_correctly()
    {
        $payload = '{"test": "data"}';
        $validSignature = 'sha256=' . hash_hmac('sha256', $payload, 'test-secret');
        $invalidSignature = 'sha256=invalid';

        $method = new \ReflectionMethod($this->service, 'verifyGitHubSignature');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, $payload, $validSignature, 'test-secret'));
        $this->assertFalse($method->invoke($this->service, $payload, $invalidSignature, 'test-secret'));
    }

    public function test_matches_pull_request_event_actions_correctly()
    {
        $trigger = Trigger::factory()->create([
            'repository_id' => $this->repository->id,
            'event_filters' => [
                'pull_request' => [
                    'actions' => ['closed'],
                    'merged_only' => true
                ]
            ]
        ]);

        $method = new \ReflectionMethod($this->service, 'matchesEventAction');
        $method->setAccessible(true);

        // Should match: closed + merged
        $matchingData = [
            'action' => 'closed',
            'pull_request' => ['merged' => true]
        ];
        $this->assertTrue($method->invoke($this->service, $trigger, 'pull_request', $matchingData));

        // Should not match: closed but not merged
        $nonMatchingData = [
            'action' => 'closed',
            'pull_request' => ['merged' => false]
        ];
        $this->assertFalse($method->invoke($this->service, $trigger, 'pull_request', $nonMatchingData));

        // Should not match: wrong action
        $wrongActionData = [
            'action' => 'opened',
            'pull_request' => ['merged' => true]
        ];
        $this->assertFalse($method->invoke($this->service, $trigger, 'pull_request', $wrongActionData));
    }

    public function test_matches_push_event_branch_filters_correctly()
    {
        $trigger = Trigger::factory()->create([
            'repository_id' => $this->repository->id,
            'event_filters' => [
                'push' => [
                    'branches' => ['main', 'develop']
                ]
            ]
        ]);

        $method = new \ReflectionMethod($this->service, 'matchesEventAction');
        $method->setAccessible(true);

        // Should match: push to main branch
        $mainBranchData = [
            'ref' => 'refs/heads/main'
        ];
        $this->assertTrue($method->invoke($this->service, $trigger, 'push', $mainBranchData));

        // Should match: push to develop branch
        $developBranchData = [
            'ref' => 'refs/heads/develop'
        ];
        $this->assertTrue($method->invoke($this->service, $trigger, 'push', $developBranchData));

        // Should not match: push to feature branch
        $featureBranchData = [
            'ref' => 'refs/heads/feature/new-feature'
        ];
        $this->assertFalse($method->invoke($this->service, $trigger, 'push', $featureBranchData));
    }

    public function test_determines_target_users_for_all_targeting()
    {
        // Create collaborators
        $collaborator1 = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 12345
        ]);

        $collaborator2 = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 67890
        ]);

        $trigger = Trigger::factory()->create([
            'repository_id' => $this->repository->id,
            'target_type' => 'all'
        ]);

        $method = new \ReflectionMethod($this->service, 'determineTargetUsers');
        $method->setAccessible(true);

        $targetUsers = $method->invoke($this->service, $trigger, $this->repository, []);

        $this->assertEquals(2, $targetUsers->count());
        $this->assertTrue($targetUsers->contains('github_user_id', 12345));
        $this->assertTrue($targetUsers->contains('github_user_id', 67890));
    }

    public function test_determines_target_users_for_specific_user_targeting()
    {
        // Create collaborators
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

        $method = new \ReflectionMethod($this->service, 'determineTargetUsers');
        $method->setAccessible(true);

        $targetUsers = $method->invoke($this->service, $trigger, $this->repository, []);

        $this->assertEquals(1, $targetUsers->count());
        $this->assertEquals(12345, $targetUsers->first()->github_user_id);
    }

    public function test_determines_target_users_for_role_targeting()
    {
        $role = Role::factory()->create();

        // Create collaborators with roles
        $collaborator1 = RepositoryCollaborator::factory()->withRole()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 12345,
            'vault_role_id' => $role->id
        ]);

        $collaborator2 = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 67890,
            'vault_role_id' => null // No role assigned
        ]);

        $trigger = Trigger::factory()->targetingRoles([$role->id])->create([
            'repository_id' => $this->repository->id
        ]);

        $method = new \ReflectionMethod($this->service, 'determineTargetUsers');
        $method->setAccessible(true);

        $targetUsers = $method->invoke($this->service, $trigger, $this->repository, []);

        $this->assertEquals(1, $targetUsers->count());
        $this->assertEquals(12345, $targetUsers->first()->github_user_id);
    }

    public function test_ignores_inactive_triggers()
    {
        $trigger = Trigger::factory()->inactive()->create([
            'repository_id' => $this->repository->id
        ]);

        RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id
        ]);

        $payload = json_encode([
            'repository' => ['id' => $this->repository->github_id],
            'sender' => ['id' => 12345]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->repository->webhook_secret);

        $result = $this->service->process(
            $payload,
            'push',
            'test-delivery-123',
            $signature
        );

        $this->assertTrue($result);
        Queue::assertNothingPushed();
    }

    public function test_processes_valid_webhook_and_dispatches_jobs()
    {
        $trigger = Trigger::factory()->forPushEvents()->create([
            'repository_id' => $this->repository->id,
            'target_type' => 'all'
        ]);

        RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 12345
        ]);

        $payload = json_encode([
            'repository' => [
                'id' => $this->repository->github_id,
                'name' => $this->repository->name
            ],
            'ref' => 'refs/heads/main',
            'commits' => [
                [
                    'id' => 'abc123',
                    'message' => 'Test commit'
                ]
            ],
            'sender' => [
                'id' => 12345,
                'login' => 'testuser'
            ]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->repository->webhook_secret);

        $result = $this->service->process(
            $payload,
            'push',
            'test-delivery-123',
            $signature
        );

        $this->assertTrue($result);
        Queue::assertPushed(OpenBrowserTabJob::class);

        $this->assertDatabaseHas('trigger_deliveries', [
            'trigger_id' => $trigger->id,
            'target_user_id' => 12345,
            'github_event_type' => 'push',
            'status' => 'pending'
        ]);
    }

    public function test_rejects_webhook_with_invalid_signature()
    {
        $trigger = Trigger::factory()->create([
            'repository_id' => $this->repository->id
        ]);

        $payload = json_encode([
            'repository' => ['id' => $this->repository->github_id]
        ]);

        $invalidSignature = 'sha256=invalid-signature';

        $result = $this->service->process(
            $payload,
            'push',
            'test-delivery-123',
            $invalidSignature
        );

        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_handles_repository_not_found()
    {
        $payload = json_encode([
            'repository' => ['id' => 999999999] // Non-existent repository
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'any-secret');

        $result = $this->service->process(
            $payload,
            'push',
            'test-delivery-123',
            $signature
        );

        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_creates_delivery_with_correct_token_and_url()
    {
        $trigger = Trigger::factory()->create([
            'repository_id' => $this->repository->id,
            'target_type' => 'all'
        ]);

        $collaborator = RepositoryCollaborator::factory()->create([
            'repository_id' => $this->repository->id,
            'github_user_id' => 12345
        ]);

        $payload = json_encode([
            'repository' => [
                'id' => $this->repository->github_id,
                'name' => $this->repository->name
            ],
            'sender' => ['id' => 12345]
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $this->repository->webhook_secret);

        $this->service->process($payload, 'push', 'test-delivery-123', $signature);

        $delivery = \App\Models\TriggerDelivery::latest()->first();

        $this->assertNotNull($delivery->prompt_token);
        $this->assertEquals(32, strlen($delivery->prompt_token));
        $this->assertStringContains('/prompt/', $delivery->delivery_url);
        $this->assertStringContains($delivery->prompt_token, $delivery->delivery_url);
    }
}