<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class ApiTokenRevokeCommand extends Command
{
    protected $signature = 'api-token:revoke
        {id : The api_tokens.id to revoke}
        {--force : Allow revoking a protected system token}';

    protected $description = 'Revoke a knowledge API token so it can no longer authenticate.';

    public function handle(): int
    {
        $token = ApiToken::find($this->argument('id'));

        if ($token === null) {
            $this->error('No API token found with id '.$this->argument('id').'.');

            return self::FAILURE;
        }

        if ($token->isRevoked()) {
            $this->warn('Token #'.$token->id.' ('.$token->name.') was already revoked at '.$token->revoked_at->toDateTimeString().'.');

            return self::SUCCESS;
        }

        if ($token->is_protected && ! $this->option('force')) {
            $this->error('This is a protected system token. Use --force only after the connected service has been reconfigured.');

            return self::FAILURE;
        }

        $token->revoked_at = now();
        $token->save();

        $this->info('Revoked API token #'.$token->id.' ('.$token->name.').');

        return self::SUCCESS;
    }
}
