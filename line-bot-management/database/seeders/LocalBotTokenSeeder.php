<?php

namespace Database\Seeders;

use App\Models\ApiToken;
use Illuminate\Database\Seeder;

/**
 * Local/dev convenience: registers the API token the LINE bot container uses,
 * taking the plaintext from the BOT_API_TOKEN environment variable so both
 * containers can share one secret injected at compose time. Only the SHA-256
 * hash is stored. Skips silently when the variable is unset (production path
 * is `php artisan api-token:issue`).
 */
class LocalBotTokenSeeder extends Seeder
{
    public function run(): void
    {
        $plainText = env('BOT_API_TOKEN');

        if (blank($plainText)) {
            $this->command?->warn('BOT_API_TOKEN is not set — skipping local bot token seeding.');

            return;
        }

        ApiToken::updateOrCreate(
            ['name' => 'line-bot-service-local'],
            [
                'token_hash' => hash('sha256', $plainText),
                'prefix' => 'local',
                'abilities' => ['read', 'analytics:write'],
                'is_protected' => true,
                'expires_at' => null,
                'revoked_at' => null,
            ]
        );

        $this->command?->info('Local bot API token ensured.');
    }
}
