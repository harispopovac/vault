<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WebhookProcessingService;

class SimpleUnitTest extends TestCase
{
    public function test_webhook_processing_service_exists()
    {
        $service = new WebhookProcessingService();
        $this->assertInstanceOf(WebhookProcessingService::class, $service);
    }

    public function test_webhook_service_has_required_methods()
    {
        $service = new WebhookProcessingService();

        $this->assertTrue(method_exists($service, 'processWebhook'));
        $this->assertTrue(method_exists($service, 'testWebhook'));
    }

    public function test_hmac_signature_generation_works()
    {
        $payload = '{"test": "data"}';
        $secret = 'test-secret';

        $signature = hash_hmac('sha256', $payload, $secret);
        $expectedSignature = 'sha256=' . $signature;

        $this->assertNotEmpty($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 produces 64 char hex string
        $this->assertStringStartsWith('sha256=', $expectedSignature);
    }

    public function test_basic_json_encoding()
    {
        $data = [
            'repository' => ['id' => 12345],
            'action' => 'opened',
            'sender' => ['login' => 'testuser']
        ];

        $json = json_encode($data);
        $decoded = json_decode($json, true);

        $this->assertEquals($data, $decoded);
        $this->assertEquals(12345, $decoded['repository']['id']);
    }
}