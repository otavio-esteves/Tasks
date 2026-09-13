<?php

namespace App\Application\System;

use App\Application\System\Contracts\SystemSettingRepository;
use App\Application\System\Data\SystemAccessData;
use App\Application\Users\Contracts\UserRepository;
use App\Domain\System\Exceptions\InvalidPublicAccessUser;

class UpdateSystemAccess
{
    public function __construct(
        private readonly SystemSettingRepository $settings,
        private readonly UserRepository $users,
    ) {}

    public function handle(SystemAccessData $data): SystemAccessData
    {
        if (! $data->loginRequired) {
            $user = $data->publicUserId === null ? null : $this->users->find($data->publicUserId);

            if ($user === null || $user->isAdmin() || $user->team_id === null) {
                throw new InvalidPublicAccessUser;
            }
        }

        return $this->settings->saveAccess(new SystemAccessData(
            loginRequired: $data->loginRequired,
            publicUserId: $data->publicUserId,
        ));
    }
}
