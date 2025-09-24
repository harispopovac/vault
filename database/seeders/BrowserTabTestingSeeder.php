<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organisation;
use App\Models\Repository;
use App\Models\RepositoryCollaborator;
use App\Models\Prompt;
use App\Models\Trigger;
use App\Models\TriggerDelivery;

class BrowserTabTestingSeeder extends Seeder
{
    public function run(): void
    {
        // Create organisation
        $organisation = Organisation::factory()->create([
            'name' => 'Browser Tab Test Org'
        ]);

        // Create repository
        $repository = Repository::factory()->withWebhookSecret('browser-tab-secret')->create([
            'name' => 'browser-tab-test',
            'github_id' => 555666777,
            'organisation_id' => $organisation->id
        ]);

        // Create collaborators
        $user1 = RepositoryCollaborator::factory()->create([
            'repository_id' => $repository->id,
            'github_user_id' => 11111,
            'github_username' => 'tab-user-1',
            'is_active' => true
        ]);

        $user2 = RepositoryCollaborator::factory()->create([
            'repository_id' => $repository->id,
            'github_user_id' => 22222,
            'github_username' => 'tab-user-2',
            'is_active' => true
        ]);

        // Create prompt for browser tab testing
        $prompt = Prompt::factory()->create([
            'name' => 'Browser Tab Test Prompt',
            'description' => 'Testing browser tab notifications',
            'organisation_id' => $organisation->id,
            'created_by' => $user1->github_user_id,
            'field_definitions' => [
                'fields' => [
                    [
                        'id' => 'test_field_1',
                        'type' => 'text',
                        'label' => 'What happened in this event?',
                        'required' => true
                    ],
                    [
                        'id' => 'test_field_2',
                        'type' => 'text',
                        'label' => 'Additional notes',
                        'required' => false
                    ]
                ]
            ]
        ]);

        // Create trigger for testing
        $trigger = Trigger::factory()->create([
            'repository_id' => $repository->id,
            'prompt_id' => $prompt->id,
            'name' => 'Browser Tab Test Trigger',
            'github_events' => ['push', 'pull_request'],
            'target_type' => 'all',
            'is_active' => true
        ]);

        // Create deliveries in different states for SSE testing

        // Pending delivery (should appear in SSE stream)
        $pendingDelivery = TriggerDelivery::factory()->create([
            'trigger_id' => $trigger->id,
            'target_user_id' => 11111,
            'github_event_type' => 'push',
            'status' => 'pending',
            'github_payload' => [
                'ref' => 'refs/heads/main',
                'commits' => [['id' => 'test123', 'message' => 'Test commit']]
            ]
        ]);

        // Delivered delivery (should be marked as delivered)
        $deliveredDelivery = TriggerDelivery::factory()->delivered()->create([
            'trigger_id' => $trigger->id,
            'target_user_id' => 22222,
            'github_event_type' => 'pull_request',
            'github_payload' => [
                'action' => 'opened',
                'pull_request' => ['title' => 'Test PR', 'number' => 1]
            ]
        ]);

        // Opened delivery (user clicked the notification)
        $openedDelivery = TriggerDelivery::factory()->delivered()->create([
            'trigger_id' => $trigger->id,
            'target_user_id' => 11111,
            'github_event_type' => 'push',
            'status' => 'delivered',
            'opened_at' => now()->subMinutes(5),
            'github_payload' => [
                'ref' => 'refs/heads/feature/test',
                'commits' => [['id' => 'opened123', 'message' => 'Opened test']]
            ]
        ]);

        // Responded delivery (user filled out the form)
        $respondedDelivery = TriggerDelivery::factory()->responded()->create([
            'trigger_id' => $trigger->id,
            'target_user_id' => 22222,
            'github_event_type' => 'pull_request',
            'github_payload' => [
                'action' => 'closed',
                'pull_request' => ['title' => 'Completed PR', 'number' => 2, 'merged' => true]
            ],
            'response_data' => [
                'test_field_1' => 'This PR merged successfully',
                'test_field_2' => 'No issues encountered during merge'
            ]
        ]);

        // Expired delivery (older than 24 hours)
        $expiredDelivery = TriggerDelivery::factory()->expired()->create([
            'trigger_id' => $trigger->id,
            'target_user_id' => 11111,
            'github_event_type' => 'push',
            'github_payload' => [
                'ref' => 'refs/heads/old-branch',
                'commits' => [['id' => 'expired123', 'message' => 'Old commit']]
            ]
        ]);

        // Create multiple pending deliveries for the same user (for batch testing)
        for ($i = 1; $i <= 3; $i++) {
            TriggerDelivery::factory()->create([
                'trigger_id' => $trigger->id,
                'target_user_id' => 11111,
                'github_event_type' => 'push',
                'status' => 'pending',
                'github_payload' => [
                    'ref' => "refs/heads/batch-test-{$i}",
                    'commits' => [['id' => "batch{$i}", 'message' => "Batch test commit {$i}"]]
                ]
            ]);
        }

        // Create deliveries for different users to test user-specific filtering
        TriggerDelivery::factory()->create([
            'trigger_id' => $trigger->id,
            'target_user_id' => 99999, // Different user ID
            'github_event_type' => 'push',
            'status' => 'pending',
            'github_payload' => [
                'ref' => 'refs/heads/other-user',
                'commits' => [['id' => 'other123', 'message' => 'Other user commit']]
            ]
        ]);
    }
}