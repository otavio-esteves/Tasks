<?php

namespace App\Application\Users;

use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Users\Contracts\UserRepository;
use App\Application\Users\Data\CreateUserData;
use App\Domain\Users\Exceptions\InvalidUserTeam;
use App\Domain\Users\Exceptions\UserEmailAlreadyExists;
use App\Models\User;

class CreateUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TeamRepository $teams,
    ) {}

    public function handle(CreateUserData $data): User
    {
        if ($this->users->emailExists($data->email)) {
            throw new UserEmailAlreadyExists;
        }

        if (! $data->isAdministrator && $data->teamId === null) {
            throw new InvalidUserTeam;
        }

        if ($data->teamId !== null && $this->teams->findById($data->teamId) === null) {
            throw new InvalidUserTeam;
        }

        return $this->users->create($data);
    }
}
