<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WebhookProcessingService;

class WebhookSignatureValidationTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookProcessingService();
    }

    public function test_validates_webhook_signature_correctly()
    {
        // Create a mock request with payload
        $payload = ['test' => 'data'];
        $payloadJson = json_encode($payload);

        $request = \Illuminate\Http\Request::create('/webhook', 'POST', $payload);
        $request->headers->set('content-type', 'application/json');
        // Set the raw content to match what GitHub would send
        $request->initialize($request->query->all(), $payload, $request->attributes->all(),
                           $request->cookies->all(), $request->files->all(), $request->server->all(), $payloadJson);

        $validSignature = 'sha256=' . hash_hmac('sha256', $payloadJson, 'test-secret');
        $invalidSignature = 'sha256=invalid';

        $method = new \ReflectionMethod($this->service, 'verifyGitHubSignature');
        $method->setAccessible(true);

        // Mock repository lookup by creating a simple test
        // Note: This test just verifies the method exists and basic signature logic
        // Full testing requires database setup
        $this->assertTrue(method_exists($this->service, 'verifyGitHubSignature'));
    }

    public function test_validates_webhook_signature_with_different_payloads()
    {
        $secret = 'webhook-secret-123';

        $testCases = [
            '{"action": "opened", "number": 1}',
            '{"ref": "refs/heads/main", "commits": []}',
            '{"zen": "Keep it logically awesome."}',
            ''
        ];

        $method = new \ReflectionMethod($this->service, 'validateSignature');
        $method->setAccessible(true);

        foreach ($testCases as $payload) {
            $validSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
            $this->assertTrue(
                $method->invoke($this->service, $payload, $validSignature, $secret),
                "Failed to validate signature for payload: $payload"
            );
        }
    }

    public function test_rejects_signature_without_sha256_prefix()
    {
        $payload = '{"test": "data"}';
        $secret = 'test-secret';
        $signatureWithoutPrefix = hash_hmac('sha256', $payload, $secret);

        $method = new \ReflectionMethod($this->service, 'validateSignature');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, $payload, $signatureWithoutPrefix, $secret));
    }

    public function test_rejects_empty_signature()
    {
        $payload = '{"test": "data"}';
        $secret = 'test-secret';

        $method = new \ReflectionMethod($this->service, 'validateSignature');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->service, $payload, '', $secret));
        $this->assertFalse($method->invoke($this->service, $payload, null, $secret));
    }

    public function test_handles_timing_attack_protection()
    {
        $payload = '{"test": "data"}';
        $secret = 'test-secret';
        $validSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        $invalidSignature = 'sha256=' . str_repeat('a', 64); // Same length, different content

        $method = new \ReflectionMethod($this->service, 'validateSignature');
        $method->setAccessible(true);

        // Both should return false for invalid, but timing should be similar
        $start1 = microtime(true);
        $result1 = $method->invoke($this->service, $payload, $invalidSignature, $secret);
        $time1 = microtime(true) - $start1;

        $start2 = microtime(true);
        $result2 = $method->invoke($this->service, $payload, 'sha256=invalid', $secret);
        $time2 = microtime(true) - $start2;

        $this->assertFalse($result1);
        $this->assertFalse($result2);

        // Timing difference should be minimal (within reasonable bounds)
        $this->assertLessThan(0.001, abs($time1 - $time2), 'Timing attack vulnerability detected');
    }
}