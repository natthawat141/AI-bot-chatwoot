<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class ApiTokenIssueCommand extends Command
{
    protected $signature = 'api-token:issue
        {name : A human-readable label for the token}
        {--expires= : Days until the token expires (omit for no expiry)}
        {--ability=* : Abilities granted to the token (default: read)}
        {--protected : Prevent revocation from the admin website}';

    protected $description = 'Issue a new revocable knowledge API token and print the plaintext once.';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        $abilities = $this->option('ability');
        if (empty($abilities)) {
            $abilities = ['read'];
        }

        $expiresAt = null;
        $expires = $this->option('expires');
        if ($expires !== null && $expires !== '') {
            $days = (int) $expires;
            if ($days < 1) {
                $this->error('The --expires option must be a positive number of days.');

                return self::FAILURE;
            }
            $expiresAt = now()->addDays($days);
        }

        ['token' => $token, 'plainText' => $plainText] = ApiToken::issue(
            $name,
            $abilities,
            $expiresAt,
            (bool) $this->option('protected'),
        );

        $this->info('API token issued.');
        $this->newLine();
        $this->line('  ID       : '.$token->id);
        $this->line('  Name     : '.$token->name);
        $this->line('  Prefix   : '.$token->prefix);
        $this->line('  Abilities: '.implode(', ', $token->abilities ?? []));
        $this->line('  Expires  : '.($token->expires_at?->toDateTimeString() ?? 'never'));
        $this->line('  Protected: '.($token->is_protected ? 'yes' : 'no'));
        $this->newLine();
        $this->warn('Plaintext token (shown ONCE — it cannot be retrieved again):');
        $this->line('  '.$plainText);

        return self::SUCCESS;
    }
}
