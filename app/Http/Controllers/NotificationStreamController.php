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
            $user = Auth::user();
            if (!$user) {
                echo "data: " . json_encode(['error' => 'unauthorized']) . "\n\n";
                return;
            }

            // Send initial connection message
            echo "data: " . json_encode([
                'type' => 'connected',
                'user_id' => $user->id,
                'timestamp' => now()->toISOString()
            ]) . "\n\n";
            flush();

            $heartbeatCounter = 0;
            while (true) {
                // Check for browser tab triggers
                $cacheKey = "browser_tab_trigger:{$user->id}";
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
                    flush();
                }

                // Send heartbeat every 30 seconds (15 iterations * 2 seconds)
                $heartbeatCounter++;
                if ($heartbeatCounter >= 15) {
                    echo "data: " . json_encode([
                        'type' => 'heartbeat',
                        'timestamp' => now()->toISOString()
                    ]) . "\n\n";
                    $heartbeatCounter = 0;
                    flush();
                }

                if (connection_aborted()) {
                    break;
                }

                sleep(2); // Check every 2 seconds
            }
        });

        return $response;
    }
}