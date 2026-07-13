<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRecord extends Model
{
    protected $fillable = [
        'user_id',
        'resource',
        'filename',
        'status',
        'rows_imported',
        'rows_failed',
        'rows_skipped',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'rows_imported' => 'integer',
            'rows_failed' => 'integer',
            'rows_skipped' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
