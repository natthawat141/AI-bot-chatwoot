<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BotInteractionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'string', 'max:255'],
            'message_id' => ['nullable', 'string', 'max:255'],
            'user_hash' => ['nullable', 'string', 'size:64'],
            'question' => ['required', 'string', 'max:5000'],
            'answer' => ['required', 'string', 'max:10000'],
            'response_type' => ['required', Rule::in(['ai', 'location', 'fallback', 'non_text'])],
            'status' => ['required', Rule::in(['answered', 'failed'])],
            'model' => ['nullable', 'string', 'max:120'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:300000'],
        ]);

        $interaction = BotInteraction::firstOrCreate(
            ['event_id' => $validated['event_id']],
            $validated,
        );

        return response()->json([
            'data' => ['id' => $interaction->id],
        ], $interaction->wasRecentlyCreated ? 201 : 200);
    }
}
