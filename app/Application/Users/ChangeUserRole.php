<?php

namespace App\Application\Users;

use App\Application\System\Contracts\SystemSettingRepository;
use App\Application\Users\Contracts\UserRepository;
use App\Domain\Users\Exceptions\LastAdministratorRequired;
use App\Domain\Users\Exceptions\PublicAccessUserCannotBeAdministrator;
use App\Domain\Users\Exceptions\UserNotFound;
use App\Models\User;

class ChangeUserRole
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly SystemSettingRepository $settings,
    ) {}

    public function handle(int $userId, bool $isAdministrator): User
    {
        $user = $this->users->find($userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        if (! $isAdministrator && $user->isAdmin() && $this->users->countAdministrators() <= 1) {
            throw new LastAdministratorRequired;
        }

        $access = $this->settings->getAccess();
        if ($isAdministrator && ! $access->loginRequired && $access->publicUserId === $user->id) {
            throw new PublicAccessUserCannotBeAdministrator;
        }

        return $this->users->setAdministrator($user, $isAdministrator);
    }
}
