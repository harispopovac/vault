<?php

namespace App\Jobs;

use App\Models\TriggerDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenBrowserTabJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $delivery;

    /**
     * Create a new job instance.
     */
    public function __construct(TriggerDelivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Use Server-Sent Events to trigger browser tab opening
        $this->triggerServerSentEvent();
    }

    private function triggerServerSentEvent()
    {
        // Store the delivery instruction that will be picked up by SSE endpoint
        $cacheKey = "browser_tab_trigger:{$this->delivery->target_user_id}";

        $triggerData = [
            'url' => $this->delivery->delivery_url,
            'delivery_id' => $this->delivery->id,
            'timestamp' => now()->toISOString(),
            'trigger_name' => $this->delivery->trigger->name,
            'repository' => $this->delivery->github_payload['repository']['name'] ?? 'Unknown',
            'event_type' => $this->delivery->github_event_type
        ];

        Cache::put($cacheKey, $triggerData, 300); // 5 minute TTL

        Log::info('Browser tab trigger cached for SSE pickup', [
            'delivery_id' => $this->delivery->id,
            'user_id' => $this->delivery->target_user_id,
            'url' => $this->delivery->delivery_url,
            'trigger' => $this->delivery->trigger->name
        ]);
    }
}