# Testing Infrastructure

This document outlines the comprehensive testing infrastructure for the Knowledge Vault project.

## Overview

The testing suite includes:
- **Model Factories** for generating test data
- **Feature Tests** for webhook processing and browser tab notifications
- **Unit Tests** for core services like WebhookProcessingService
- **Database Seeders** for consistent test data
- **CI/CD Pipeline** with GitHub Actions

## Quick Start

### Running Tests Locally

```bash
# Install dependencies
composer install
npm install

# Copy environment configuration
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed test data
php artisan db:seed --class=TestDataSeeder

# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run tests with coverage
php artisan test --coverage
```

### Running Specific Tests

```bash
# Webhook processing tests
php artisan test tests/Feature/BrowserTabNotificationTest.php
php artisan test tests/Unit/WebhookProcessingServiceTest.php

# Run tests with verbose output
php artisan test --verbose

# Run a specific test method
php artisan test --filter=test_webhook_creates_browser_tab_delivery
```

## Test Data Setup

### Database Seeders

We have several seeders for different testing scenarios:

1. **TestDataSeeder** - Comprehensive test data for all testing
2. **WebhookTestingSeeder** - Focused on webhook functionality
3. **BrowserTabTestingSeeder** - Browser tab notification testing

```bash
# Seed all test data
php artisan db:seed --class=TestDataSeeder

# Seed only webhook test data
php artisan db:seed --class=WebhookTestingSeeder

# Seed only browser tab test data
php artisan db:seed --class=BrowserTabTestingSeeder
```

### Test Repository Configuration

The test data creates repositories with these webhook secrets:
- `test-webhook-secret-123` - Main webhook testing repository
- `browser-tab-secret` - Browser tab testing repository

## Model Factories

All models have comprehensive factories located in `database/factories/`:

### Core Factories
- `OrganisationFactory` - Creates test organisations
- `RepositoryFactory` - Creates repositories with webhook configuration
- `RepositoryCollaboratorFactory` - Creates collaborators with different permissions
- `TriggerFactory` - Creates triggers for different GitHub events
- `TriggerDeliveryFactory` - Creates deliveries in various states
- `PromptFactory` - Creates prompts with field definitions

### Factory Usage Examples

```php
// Create a repository with webhook secret
$repo = Repository::factory()->withWebhookSecret('my-secret')->create();

// Create a trigger for pull requests
$trigger = Trigger::factory()->forPullRequests()->create([
    'repository_id' => $repo->id
]);

// Create a delivered trigger delivery
$delivery = TriggerDelivery::factory()->delivered()->create([
    'trigger_id' => $trigger->id
]);

// Create multiple collaborators
RepositoryCollaborator::factory()->count(5)->create([
    'repository_id' => $repo->id
]);
```

## Test Suites

### Feature Tests

Located in `tests/Feature/`:

#### BrowserTabNotificationTest
Tests the complete browser tab notification workflow:
- Webhook processing creates deliveries
- Server-Sent Events (SSE) stream for real-time notifications
- Prompt display and form submission
- Status tracking (pending → delivered → opened → responded)

Key test methods:
- `test_webhook_creates_browser_tab_delivery()`
- `test_sse_endpoint_returns_pending_deliveries()`
- `test_prompt_displays_correctly()`
- `test_prompt_form_submission()`

### Unit Tests

Located in `tests/Unit/`:

#### WebhookProcessingServiceTest
Tests the core webhook processing logic:
- Webhook signature validation
- Event filtering and matching
- Target user determination
- Delivery creation

Key test methods:
- `test_validates_webhook_signature_correctly()`
- `test_matches_pull_request_event_actions_correctly()`
- `test_determines_target_users_for_all_targeting()`
- `test_processes_valid_webhook_and_dispatches_jobs()`

## GitHub Webhooks Testing

### Test Webhook Payloads

The factories generate realistic GitHub webhook payloads:

```php
// Push event payload
TriggerDelivery::factory()->create([
    'github_payload' => [
        'ref' => 'refs/heads/main',
        'commits' => [
            [
                'id' => 'abc123',
                'message' => 'Add new feature',
                'author' => ['name' => 'Developer', 'email' => 'dev@example.com']
            ]
        ],
        'repository' => ['id' => 12345, 'name' => 'test-repo'],
        'sender' => ['id' => 67890, 'login' => 'developer']
    ]
]);

// Pull request event payload
TriggerDelivery::factory()->create([
    'github_payload' => [
        'action' => 'closed',
        'pull_request' => [
            'merged' => true,
            'title' => 'Fix bug',
            'number' => 42
        ]
    ]
]);
```

### Manual Webhook Testing

```bash
# Start the application
php artisan serve

# Test webhook endpoint with curl
payload='{"test": "data"}'
signature=$(echo -n "$payload" | openssl dgst -sha256 -hmac "test-webhook-secret-123" | sed 's/^.* //')

curl -X POST http://localhost:8000/api/v1/webhook/github \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: push" \
  -H "X-GitHub-Delivery: test-delivery" \
  -H "X-Hub-Signature-256: sha256=$signature" \
  -d "$payload"
```

## CI/CD Pipeline

### GitHub Actions Workflows

Located in `.github/workflows/`:

1. **tests.yml** - Main test suite
   - Runs on PHP 8.2 and 8.3
   - Tests against PostgreSQL
   - Includes static analysis and security checks
   - Generates coverage reports

2. **webhook-tests.yml** - Specialized webhook testing
   - Integration tests for webhook endpoints
   - Browser tab notification testing
   - Real webhook payload validation

3. **security.yml** - Security and dependency checks
   - Daily security audits
   - Dependency vulnerability scanning
   - Code quality checks
   - Coverage threshold enforcement

4. **deploy.yml** - Deployment pipeline
   - Runs after tests pass
   - Staging and production deployment

### Pipeline Triggers

- **Push** to `main`, `develop`, `dev` branches
- **Pull Requests** to `main`, `develop` branches
- **Daily** security scans at 6 AM UTC
- **Manual** workflow dispatch

## Browser Tab Notification Testing

### SSE (Server-Sent Events) Testing

```bash
# Test SSE endpoint
curl -H "Accept: text/event-stream" \
     -H "Cache-Control: no-cache" \
     "http://localhost:8000/sse/notifications/12345"
```

### Frontend Integration Testing

The browser tab system involves:
1. SSE connection for real-time notifications
2. Tab opening with prompt token
3. Form display and submission
4. Status updates back to server

Test data includes deliveries in all states:
- `pending` - Just created, waiting for delivery
- `delivered` - Notification sent to browser
- `opened` - User clicked the notification
- `responded` - User submitted the form
- `expired` - Delivery older than 24 hours

## Environment Configuration

### Testing Environment

Tests use SQLite in-memory database by default (configured in `phpunit.xml`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Local Development Testing

For local development with PostgreSQL:

```bash
# Create test database
createdb vault_test

# Update .env.testing
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=vault_test
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Coverage Reports

### Generating Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html=coverage

# Generate clover XML for CI
vendor/bin/phpunit --coverage-clover=coverage.xml

# View coverage summary
vendor/bin/phpunit --coverage-text
```

### Coverage Thresholds

The CI pipeline enforces a minimum coverage threshold of 70%. This can be adjusted in `.github/workflows/security.yml`.

## Troubleshooting

### Common Issues

1. **Database connection errors**
   - Ensure test database exists
   - Check database credentials in phpunit.xml

2. **Queue jobs not processing**
   - Tests use `Queue::fake()` by default
   - Use `Queue::assertPushed()` to verify job dispatch

3. **Webhook signature validation fails**
   - Ensure webhook secret matches in test data
   - Check payload format and encoding

4. **SSE endpoint returns empty**
   - Verify test user has pending deliveries
   - Check user ID matches in request

### Debug Mode

```bash
# Run tests with debug output
php artisan test --verbose --debug

# Check logs during tests
tail -f storage/logs/laravel.log
```

## Best Practices

1. **Use factories** instead of creating models manually
2. **Isolate tests** with `RefreshDatabase` trait
3. **Mock external services** (GitHub API, etc.)
4. **Test edge cases** (invalid signatures, expired deliveries)
5. **Verify side effects** (jobs dispatched, database changes)
6. **Use descriptive test names** that explain the scenario

## Adding New Tests

### Creating a New Feature Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup test data
    }

    public function test_new_feature_works_correctly()
    {
        // Arrange
        $data = ['test' => 'data'];

        // Act
        $response = $this->postJson('/api/endpoint', $data);

        // Assert
        $response->assertStatus(200);
    }
}
```

### Creating a New Unit Test

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\YourService;

class YourServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YourService();
    }

    public function test_service_method_returns_expected_result()
    {
        $result = $this->service->yourMethod('input');
        $this->assertEquals('expected', $result);
    }
}
```

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Create appropriate factories for new models
3. Add seeders for complex test scenarios
4. Update this documentation
5. Ensure CI pipeline passes

The testing infrastructure is designed to be comprehensive, maintainable, and provide confidence in the reliability of the Knowledge Vault webhook processing and browser tab notification system.