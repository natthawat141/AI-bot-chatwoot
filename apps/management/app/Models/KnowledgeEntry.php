<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeEntry extends Model
{
    /** @use HasFactory<\Database\Factories\KnowledgeEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'type',
        'category',
        'tags',
        'source_url',
        'version',
        'is_active',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<KnowledgeEntry>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
