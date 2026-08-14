<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only business profile API consumed by the Chatwoot AI service.
 *
 * The AI service composes these fields into its system prompt as data, not
 * instructions (SPEC.md FR-AI-009, NFR-SEC-005). The anti-hallucination
 * guardrails remain hardcoded in Python; this endpoint only exposes facts.
 */
class BusinessProfileApiController extends Controller
{
    private const SCHEMA_VERSION = '1.0';

    public function show(Request $request): JsonResponse
    {
        $profile = BusinessProfile::current();

        $data = [
            'business_name' => $profile->business_name,
            'business_description' => $profile->business_description,
            'services_offered' => $profile->services_offered,
            'service_areas' => $profile->service_areas,
            'business_hours' => $profile->business_hours,
            'contact_channels' => $profile->contact_channels,
            'conversation_tone' => $profile->conversation_tone,
            'always_escalate_topics' => $profile->always_escalate_topics,
            'updated_at' => $profile->updated_at?->toIso8601String(),
        ];

        return response()->json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'version' => self::SCHEMA_VERSION,
            ],
            'data' => $data,
        ]);
    }
}
