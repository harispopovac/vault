<?php

namespace App\Http\Controllers;

use App\Models\TriggerDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromptController extends Controller
{
    public function show($token)
    {
        $delivery = TriggerDelivery::where('prompt_token', $token)
            ->with(['trigger.prompt', 'trigger.organization'])
            ->first();

        if (!$delivery) {
            abort(404, 'Prompt not found or expired');
        }

        if ($delivery->status === 'completed') {
            return view('prompt.completed', compact('delivery'));
        }

        if ($delivery->status === 'expired') {
            return view('prompt.expired', compact('delivery'));
        }

        // Mark as opened if first time
        if (!$delivery->opened_at) {
            $delivery->update([
                'opened_at' => now(),
                'status' => 'delivered'
            ]);
        }

        // Get the trigger and prompt data
        $trigger = $delivery->trigger;
        $prompt = $trigger->prompt;

        return view('prompt.show', compact('delivery', 'trigger', 'prompt'));
    }

    public function store(Request $request, $token)
    {
        $delivery = TriggerDelivery::where('prompt_token', $token)->first();

        if (!$delivery || $delivery->status === 'completed') {
            return response()->json(['error' => 'Invalid or completed prompt'], 400);
        }

        // For demo purposes, just save the raw request data
        $validatedData = $request->all();

        // Save the response
        $delivery->update([
            'status' => 'completed',
            'responded_at' => now(),
            'response_data' => $validatedData
        ]);

        Log::info('Prompt response completed', [
            'delivery_id' => $delivery->id,
            'trigger' => $delivery->trigger->name,
            'response_data' => $validatedData
        ]);

        return response()->json(['message' => 'Response saved successfully']);
    }
}