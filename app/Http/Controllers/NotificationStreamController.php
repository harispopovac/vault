<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationStreamController extends Controller
{
    public function stream(Request $request)
    {
        return response()->stream(function() {
            $userId = 1; // Fixed user ID for testing

            // Send initial connection message
            echo "data: " . json_encode([
                'type' => 'connected',
                'user_id' => $userId,
                'timestamp' => now()->toISOString()
            ]) . "\n\n";
            flush();

            // Check for existing trigger
            $cacheKey = "browser_tab_trigger:{$userId}";
            $trigger = Cache::get($cacheKey);

            if ($trigger) {
                echo "data: " . json_encode([
                    'type' => 'browser_tab',
                    'action' => 'open',
                    'url' => $trigger['url'],
                    'delivery_id' => $trigger['delivery_id'],
                    'trigger_name' => $trigger['trigger_name'],
                    'repository' => $trigger['repository'],
                    'event_type' => $trigger['event_type']
                ]) . "\n\n";
                flush();
                Cache::forget($cacheKey);
            }

            // Simple loop for checking triggers
            for ($i = 0; $i < 20; $i++) { // Reduced to 40 seconds max
                if (connection_aborted()) break;

                $trigger = Cache::get($cacheKey);
                if ($trigger) {
                    echo "data: " . json_encode([
                        'type' => 'browser_tab',
                        'action' => 'open',
                        'url' => $trigger['url'],
                        'delivery_id' => $trigger['delivery_id'],
                        'trigger_name' => $trigger['trigger_name'],
                        'repository' => $trigger['repository'],
                        'event_type' => $trigger['event_type']
                    ]) . "\n\n";
                    flush();
                    Cache::forget($cacheKey);
                }

                // Send heartbeat every 5 iterations (10 seconds)
                if ($i % 5 == 0 && $i > 0) {
                    echo "data: " . json_encode([
                        'type' => 'heartbeat',
                        'timestamp' => now()->toISOString()
                    ]) . "\n\n";
                    flush();
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Authorization',
        ]);
    }
}