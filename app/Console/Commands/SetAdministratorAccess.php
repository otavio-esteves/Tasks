<?php

namespace App\Console\Commands;

use App\Application\Users\ChangeUserRole;
use App\Models\User;
use DomainException;
use Illuminate\Console\Command;

class SetAdministratorAccess extends Command
{
    protected $signature = 'users:admin {email : Email of an existing account} {--revoke : Remove administrative access}';

    protected $description = 'Explicitly grant or revoke administrative access from the server console';

    public function handle(ChangeUserRole $changeUserRole): int
    {
        $user = User::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->error('Account not found.');

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');

        try {
            $changeUserRole->handle($user->id, ! $revoke);
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($revoke ? 'Administrative access revoked.' : 'Administrative access granted.');

        return self::SUCCESS;
    }
}
