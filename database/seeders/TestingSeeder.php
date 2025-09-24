<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Models\Repository;
use App\Models\RepositoryCollaborator;
use App\Models\Prompt;
use App\Models\Trigger;
use App\Models\TriggerDelivery;

class TestingSeeder extends Seeder
{
    public function run(): void
    {
        // Create test organisation
        $organisation = Organisation::factory()->create([
            'name' => 'Test Organisation'
        ]);

        // Create test roles
        $adminRole = Role::factory()->create([
            'name' => 'Admin',
            'organisation_id' => $organisation->id
        ]);

        $developerRole = Role::factory()->create([
            'name' => 'Developer',
            'organisation_id' => $organisation->id
        ]);

        $reviewerRole = Role::factory()->create([
            'name' => 'Reviewer',
            'organisation_id' => $organisation->id
        ]);

        // Create test users
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'github_id' => '12345',
            'github_username' => 'admin-user',
            'organisation_id' => $organisation->id
        ]);

        $developerUser = User::factory()->create([
            'name' => 'Developer User',
            'email' => 'developer@test.com',
            'github_id' => '67890',
            'github_username' => 'dev-user',
            'organisation_id' => $organisation->id
        ]);

        $reviewerUser = User::factory()->create([
            'name' => 'Reviewer User',
            'email' => 'reviewer@test.com',
            'github_id' => '11111',
            'github_username' => 'reviewer-user',
            'organisation_id' => $organisation->id
        ]);

        // Create test repositories
        $mainRepo = Repository::factory()->withWebhookSecret('test-webhook-secret')->create([
            'name' => 'knowledge-vault',
            'full_name' => 'test-org/knowledge-vault',
            'github_id' => 123456789,
            'organisation_id' => $organisation->id,
            'is_active' => true
        ]);

        $secondaryRepo = Repository::factory()->create([
            'name' => 'secondary-repo',
            'full_name' => 'test-org/secondary-repo',
            'github_id' => 987654321,
            'organisation_id' => $organisation->id,
            'is_active' => true
        ]);

        // Create repository collaborators
        RepositoryCollaborator::factory()->withRole()->create([
            'repository_id' => $mainRepo->id,
            'github_user_id' => 12345,
            'github_username' => 'admin-user',
            'github_email' => 'admin@test.com',
            'permission_level' => 'admin',
            'vault_role_id' => $adminRole->id,
            'is_active' => true
        ]);

        RepositoryCollaborator::factory()->withRole()->create([
            'repository_id' => $mainRepo->id,
            'github_user_id' => 67890,
            'github_username' => 'dev-user',
            'github_email' => 'developer@test.com',
            'permission_level' => 'write',
            'vault_role_id' => $developerRole->id,
            'is_active' => true
        ]);

        RepositoryCollaborator::factory()->withRole()->create([
            'repository_id' => $mainRepo->id,
            'github_user_id' => 11111,
            'github_username' => 'reviewer-user',
            'github_email' => 'reviewer@test.com',
            'permission_level' => 'read',
            'vault_role_id' => $reviewerRole->id,
            'is_active' => true
        ]);

        // Create test prompts
        $codeReviewPrompt = Prompt::factory()->create([
            'name' => 'Code Review Knowledge Capture',
            'description' => 'Capture knowledge from code review process',
            'field_definitions' => [
                'fields' => [
                    [
                        'id' => 'review_summary',
                        'type' => 'text',
                        'label' => 'What was reviewed and why?',
                        'required' => true
                    ],
                    [
                        'id' => 'issues_found',
                        'type' => 'text',
                        'label' => 'Issues or improvements identified',
                        'required' => false
                    ],
                    [
                        'id' => 'best_practices',
                        'type' => 'text',
                        'label' => 'Best practices applied or suggested',
                        'required' => false
                    ]
                ]
            ],
            'organisation_id' => $organisation->id,
            'created_by' => $adminUser->id
        ]);

        $deploymentPrompt = Prompt::factory()->create([
            'name' => 'Deployment Knowledge Capture',
            'description' => 'Capture knowledge from deployment process',
            'field_definitions' => [
                'fields' => [
                    [
                        'id' => 'deployment_notes',
                        'type' => 'text',
                        'label' => 'What was deployed and any issues?',
                        'required' => true
                    ],
                    [
                        'id' => 'rollback_plan',
                        'type' => 'text',
                        'label' => 'Rollback plan if needed',
                        'required' => false
                    ]
                ]
            ],
            'organisation_id' => $organisation->id,
            'created_by' => $adminUser->id
        ]);

        // Create test triggers
        $prMergeTrigger = Trigger::factory()->forPullRequests()->create([
            'repository_id' => $mainRepo->id,
            'prompt_id' => $codeReviewPrompt->id,
            'name' => 'PR Merge Knowledge Capture',
            'description' => 'Triggered when pull requests are merged',
            'target_type' => 'roles',
            'target_config' => ['role_ids' => [$developerRole->id, $reviewerRole->id]],
            'is_active' => true
        ]);

        $pushToMainTrigger = Trigger::factory()->forPushEvents()->create([
            'repository_id' => $mainRepo->id,
            'prompt_id' => $deploymentPrompt->id,
            'name' => 'Main Branch Push Trigger',
            'description' => 'Triggered when code is pushed to main branch',
            'target_type' => 'all',
            'target_config' => [],
            'event_filters' => [
                'push' => [
                    'branches' => ['main']
                ]
            ],
            'is_active' => true
        ]);

        $specificUserTrigger = Trigger::factory()->targetingSpecificUsers([12345])->create([
            'repository_id' => $mainRepo->id,
            'prompt_id' => $codeReviewPrompt->id,
            'name' => 'Admin Only Trigger',
            'description' => 'Triggered for specific admin user only',
            'is_active' => true
        ]);

        // Create some test trigger deliveries in various states
        TriggerDelivery::factory()->create([
            'trigger_id' => $prMergeTrigger->id,
            'target_user_id' => 67890,
            'github_event_type' => 'pull_request',
            'status' => 'pending',
            'github_payload' => [
                'action' => 'closed',
                'pull_request' => [
                    'merged' => true,
                    'title' => 'Add new feature',
                    'number' => 123
                ],
                'repository' => [
                    'name' => 'knowledge-vault',
                    'full_name' => 'test-org/knowledge-vault'
                ]
            ]
        ]);

        TriggerDelivery::factory()->delivered()->create([
            'trigger_id' => $pushToMainTrigger->id,
            'target_user_id' => 12345,
            'github_event_type' => 'push',
            'github_payload' => [
                'ref' => 'refs/heads/main',
                'commits' => [
                    [
                        'id' => 'abc123',
                        'message' => 'Deploy to production'
                    ]
                ],
                'repository' => [
                    'name' => 'knowledge-vault'
                ]
            ]
        ]);

        TriggerDelivery::factory()->responded()->create([
            'trigger_id' => $prMergeTrigger->id,
            'target_user_id' => 11111,
            'github_event_type' => 'pull_request',
            'github_payload' => [
                'action' => 'closed',
                'pull_request' => [
                    'merged' => true,
                    'title' => 'Fix authentication bug',
                    'number' => 124
                ]
            ],
            'response_data' => [
                'review_summary' => 'Fixed critical authentication vulnerability',
                'issues_found' => 'SQL injection vulnerability in login form',
                'best_practices' => 'Implemented parameterized queries and input validation'
            ]
        ]);

        TriggerDelivery::factory()->expired()->create([
            'trigger_id' => $specificUserTrigger->id,
            'target_user_id' => 12345,
            'github_event_type' => 'push',
            'github_payload' => [
                'ref' => 'refs/heads/feature/expired-test'
            ]
        ]);
    }
}