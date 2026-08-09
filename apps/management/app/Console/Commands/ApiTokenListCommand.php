<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class ApiTokenListCommand extends Command
{
    protected $signature = 'api-token:list';

    protected $description = 'List all knowledge API tokens (never the hash or plaintext).';

    public function handle(): int
    {
        $tokens = ApiToken::query()->orderBy('id')->get();

        if ($tokens->isEmpty()) {
            $this->info('No API tokens have been issued.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Prefix', 'Abilities', 'Protected', 'Last Used', 'Expires', 'Revoked'],
            $tokens->map(fn (ApiToken $token) => [
                $token->id,
                $token->name,
                $token->prefix,
                implode(', ', $token->abilities ?? []),
                $token->is_protected ? 'yes' : 'no',
                $token->last_used_at?->toDateTimeString() ?? '—',
                $token->expires_at?->toDateTimeString() ?? 'never',
                $token->revoked_at?->toDateTimeString() ?? '—',
            ])->all()
        );

        return self::SUCCESS;
    }
}
