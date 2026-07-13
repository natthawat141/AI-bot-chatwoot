<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotInteraction extends Model
{
    protected $fillable = [
        'event_id',
        'message_id',
        'user_hash',
        'question',
        'answer',
        'response_type',
        'status',
        'model',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
        ];
    }
}
