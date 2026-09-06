<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdministratorAccess extends Command
{
    protected $signature = 'users:admin {email : Email of an existing account} {--revoke : Remove administrative access}';

    protected $description = 'Explicitly grant or revoke administrative access from the server console';

    public function handle(): int
    {
        $user = User::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->error('Account not found.');

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');

        if (! $revoke && ! $user->hasVerifiedEmail()) {
            $this->error('Verify the account email before granting administrative access.');

            return self::FAILURE;
        }

        // Never expose this assignment through public input or mass assignment.
        $user->forceFill(['is_admin' => ! $revoke])->save();
        $this->info($revoke ? 'Administrative access revoked.' : 'Administrative access granted.');

        return self::SUCCESS;
    }
}
