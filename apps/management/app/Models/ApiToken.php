<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token_hash',
        'prefix',
        'abilities',
        'is_protected',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'is_protected' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'token_hash',
    ];

    /**
     * Issue a new token. Returns the model plus the ONE-TIME plaintext value.
     * Only the SHA-256 hash is persisted.
     *
     * @param  array<int, string>  $abilities
     * @return array{token: ApiToken, plainText: string}
     */
    public static function issue(string $name, array $abilities = ['read'], ?Carbon $expiresAt = null, bool $isProtected = false): array
    {
        $secret = Str::random(48);
        $prefix = 'lk_'.Str::lower(Str::random(8));
        $plainText = $prefix.'.'.$secret;

        $token = static::create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainText),
            'prefix' => $prefix,
            'abilities' => $abilities,
            'is_protected' => $isProtected,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plainText' => $plainText];
    }

    /**
     * Resolve a usable token from a plaintext bearer value, or null if invalid,
     * revoked, or expired.
     */
    public static function findValid(string $plainText): ?ApiToken
    {
        $token = static::query()
            ->where('token_hash', hash('sha256', $plainText))
            ->first();

        if (! $token || $token->revoked_at !== null) {
            return null;
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return null;
        }

        return $token;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
