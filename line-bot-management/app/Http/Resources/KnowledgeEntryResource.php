<?php

namespace App\Http\Resources;

use App\Models\KnowledgeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KnowledgeEntry
 */
class KnowledgeEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'category' => $this->category,
            'tags' => $this->tags,
            'source_url' => $this->source_url,
            'version' => (int) $this->version,
            'is_active' => (bool) $this->is_active,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
