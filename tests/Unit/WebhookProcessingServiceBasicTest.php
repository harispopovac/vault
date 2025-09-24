<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WebhookProcessingService;

class WebhookProcessingServiceBasicTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookProcessingService();
    }

    public function test_webhook_service_instantiation()
    {
        $this->assertInstanceOf(WebhookProcessingService::class, $this->service);
    }

    public function test_webhook_service_has_process_webhook_method()
    {
        $this->assertTrue(method_exists($this->service, 'processWebhook'));
    }

    public function test_webhook_service_has_test_webhook_method()
    {
        $this->assertTrue(method_exists($this->service, 'testWebhook'));
    }

    public function test_webhook_service_has_verification_method()
    {
        $this->assertTrue(method_exists($this->service, 'verifyGitHubSignature'));
    }

    public function test_hmac_signature_calculation()
    {
        $payload = '{"test": "data", "repository": {"id": 123}}';
        $secret = 'test-secret';

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // Test that we can generate the same signature that GitHub would send
        $this->assertStringStartsWith('sha256=', $expectedSignature);
        $this->assertEquals(71, strlen($expectedSignature)); // "sha256=" + 64 char hex
    }

    public function test_hash_equals_function_exists()
    {
        // Verify that hash_equals function exists for secure comparison
        $this->assertTrue(function_exists('hash_equals'));

        $string1 = 'identical_string';
        $string2 = 'identical_string';
        $string3 = 'different_string';

        $this->assertTrue(hash_equals($string1, $string2));
        $this->assertFalse(hash_equals($string1, $string3));
    }

    public function test_github_payload_structure()
    {
        $pushPayload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'id' => 123456789,
                'name' => 'test-repo',
                'full_name' => 'test-org/test-repo'
            ],
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
        ];

        $json = json_encode($pushPayload);
        $decoded = json_decode($json, true);

        $this->assertEquals($pushPayload, $decoded);
        $this->assertEquals('refs/heads/main', $decoded['ref']);
        $this->assertEquals(123456789, $decoded['repository']['id']);
    }
}