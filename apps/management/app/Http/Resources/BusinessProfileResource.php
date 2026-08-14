<?php

namespace App\Http\Resources;

use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessProfile
 */
class BusinessProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'business_description' => $this->business_description,
            'services_offered' => $this->services_offered,
            'service_areas' => $this->service_areas,
            'business_hours' => $this->business_hours,
            'contact_channels' => $this->contact_channels,
            'conversation_tone' => $this->conversation_tone,
            'always_escalate_topics' => $this->always_escalate_topics,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
