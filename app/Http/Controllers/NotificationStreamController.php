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
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization');

        $response->setCallback(function () {
            // Disable time limit and output buffering
            set_time_limit(0);
            if (ob_get_level()) {
                ob_end_clean();
            }

            $user = Auth::user();

            // For testing: use dummy user ID if not authenticated
            $userId = $user ? $user->id : 1;

            // Send initial connection message
            echo "data: " . json_encode([
                'type' => 'connected',
                'user_id' => $userId,
                'timestamp' => now()->toISOString()
            ]) . "\n\n";

            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Check for browser tab triggers immediately
            $cacheKey = "browser_tab_trigger:{$userId}";
            $trigger = Cache::get($cacheKey);

            if ($trigger) {
                // Send the browser tab opening instruction
                echo "data: " . json_encode([
                    'type' => 'browser_tab',
                    'action' => 'open',
                    'url' => $trigger['url'],
                    'delivery_id' => $trigger['delivery_id'],
                    'trigger_name' => $trigger['trigger_name'],
                    'repository' => $trigger['repository'],
                    'event_type' => $trigger['event_type']
                ]) . "\n\n";

                // Clear the trigger
                Cache::forget($cacheKey);

                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }

            // Keep connection alive with heartbeats
            $heartbeatCounter = 0;
            while (!connection_aborted() && $heartbeatCounter < 30) {
                // Send heartbeat every 10 seconds
                if ($heartbeatCounter % 5 == 0) {
                    echo "data: " . json_encode([
                        'type' => 'heartbeat',
                        'timestamp' => now()->toISOString()
                    ]) . "\n\n";

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }

                // Check for new triggers every iteration
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

                    Cache::forget($cacheKey);

                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }

                sleep(2);
                $heartbeatCounter++;
            }
        });

        return $response;
    }
}