<?php

namespace App\Application\System\Queries;

use App\Application\Users\Contracts\UserRepository;
use App\Models\User;

class GetPublicAccessUser
{
    public function __construct(
        private readonly GetSystemAccess $getSystemAccess,
        private readonly UserRepository $users,
    ) {}

    public function handle(): ?User
    {
        $access = $this->getSystemAccess->handle();

        if ($access->loginRequired || $access->publicUserId === null) {
            return null;
        }

        $user = $this->users->find($access->publicUserId);

        if ($user === null || $user->isAdmin() || $user->team_id === null) {
            return null;
        }

        return $user;
    }
}
