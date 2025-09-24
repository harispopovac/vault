<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organisation;
use App\Models\Repository;
use App\Models\RepositoryCollaborator;
use App\Models\Prompt;
use App\Models\Trigger;
use App\Models\TriggerDelivery;

class WebhookTestingSeeder extends Seeder
{
    public function run(): void
    {
        // Create test organisation
        $organisation = Organisation::factory()->create([
            'name' => 'Webhook Test Organisation'
        ]);

        // Create test repository with webhook secret
        $repository = Repository::factory()->withWebhookSecret('test-webhook-secret-123')->create([
            'name' => 'webhook-test-repo',
            'full_name' => 'test-org/webhook-test-repo',
            'github_id' => 123456789,
            'organisation_id' => $organisation->id,
            'is_active' => true
        ]);

        // Create test collaborators
        $collaborator1 = RepositoryCollaborator::factory()->create([
            'repository_id' => $repository->id,
            'github_user_id' => 12345,
            'github_username' => 'test-user-1',
            'github_email' => 'user1@test.com',
            'permission_level' => 'admin',
            'is_active' => true
        ]);

        $collaborator2 = RepositoryCollaborator::factory()->create([
            'repository_id' => $repository->id,
            'github_user_id' => 67890,
            'github_username' => 'test-user-2',
            'github_email' => 'user2@test.com',
            'permission_level' => 'write',
            'is_active' => true
        ]);

        // Create test prompt
        $prompt = Prompt::factory()->create([
            'name' => 'Webhook Test Prompt',
            'description' => 'Test prompt for webhook integration',
            'organisation_id' => $organisation->id,
            'created_by' => $collaborator1->github_user_id
        ]);

        // Create test triggers
        $pushTrigger = Trigger::factory()->forPushEvents()->create([
            'repository_id' => $repository->id,
            'prompt_id' => $prompt->id,
            'name' => 'Push Event Trigger',
            'description' => 'Triggered on push events to main branch',
            'target_type' => 'all',
            'target_config' => [],
            'event_filters' => [
                'push' => [
                    'branches' => ['main', 'develop']
                ]
            ],
            'is_active' => true
        ]);

        $prTrigger = Trigger::factory()->forPullRequests()->create([
            'repository_id' => $repository->id,
            'prompt_id' => $prompt->id,
            'name' => 'PR Merge Trigger',
            'description' => 'Triggered when PRs are merged',
            'target_type' => 'specific_users',
            'target_config' => ['user_ids' => [12345, 67890]],
            'is_active' => true
        ]);

        // Create some test deliveries
        $pendingDelivery = TriggerDelivery::factory()->create([
            'trigger_id' => $pushTrigger->id,
            'target_user_id' => 12345,
            'github_event_type' => 'push',
            'status' => 'pending',
            'github_payload' => [
                'ref' => 'refs/heads/main',
                'commits' => [
                    [
                        'id' => 'abc123def456',
                        'message' => 'Add new webhook feature',
                        'author' => [
                            'name' => 'Test User 1',
                            'email' => 'user1@test.com'
                        ]
                    ]
                ],
                'repository' => [
                    'id' => 123456789,
                    'name' => 'webhook-test-repo',
                    'full_name' => 'test-org/webhook-test-repo'
                ],
                'sender' => [
                    'id' => 12345,
                    'login' => 'test-user-1'
                ]
            ]
        ]);

        $deliveredDelivery = TriggerDelivery::factory()->delivered()->create([
            'trigger_id' => $prTrigger->id,
            'target_user_id' => 67890,
            'github_event_type' => 'pull_request',
            'github_payload' => [
                'action' => 'closed',
                'pull_request' => [
                    'id' => 1,
                    'number' => 42,
                    'title' => 'Fix webhook integration',
                    'merged' => true,
                    'base' => ['ref' => 'main'],
                    'head' => ['ref' => 'feature/webhook-fix']
                ],
                'repository' => [
                    'id' => 123456789,
                    'name' => 'webhook-test-repo'
                ],
                'sender' => [
                    'id' => 67890,
                    'login' => 'test-user-2'
                ]
            ]
        ]);

        $respondedDelivery = TriggerDelivery::factory()->responded()->create([
            'trigger_id' => $pushTrigger->id,
            'target_user_id' => 12345,
            'github_event_type' => 'push',
            'github_payload' => [
                'ref' => 'refs/heads/develop',
                'commits' => [
                    [
                        'id' => 'def789ghi012',
                        'message' => 'Update documentation'
                    ]
                ]
            ],
            'response_data' => [
                'knowledge_context' => 'Updated webhook documentation with new examples',
                'technical_details' => 'Added signature validation and error handling examples',
                'lessons_learned' => 'Webhook testing requires careful payload structure validation',
                'future_considerations' => 'Consider adding webhook retry mechanism'
            ]
        ]);

        // Create an inactive repository for testing edge cases
        $inactiveRepo = Repository::factory()->create([
            'name' => 'inactive-repo',
            'full_name' => 'test-org/inactive-repo',
            'github_id' => 987654321,
            'organisation_id' => $organisation->id,
            'is_active' => false
        ]);

        // Create inactive trigger for testing
        $inactiveTrigger = Trigger::factory()->inactive()->create([
            'repository_id' => $repository->id,
            'prompt_id' => $prompt->id,
            'name' => 'Inactive Trigger',
            'description' => 'This trigger should not fire'
        ]);
    }
}