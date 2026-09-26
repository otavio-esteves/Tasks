<?php

namespace App\Application\Users;

use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Users\Contracts\UserRepository;
use App\Domain\Users\Exceptions\InvalidUserTeam;
use App\Domain\Users\Exceptions\UserNotFound;
use App\Models\User;

class ChangeUserTeams
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TeamRepository $teams,
    ) {}

    /** @param list<int> $teamIds */
    public function handle(int $userId, array $teamIds, int $actorId): User
    {
        $user = $this->users->find($userId) ?? throw new UserNotFound;

        $teamIds = array_values(array_unique($teamIds));

        if ((! $user->isAdmin() && $teamIds === [])
            || collect($teamIds)->contains(fn (int $teamId): bool => $this->teams->findById($teamId) === null)) {
            throw new InvalidUserTeam;
        }

        return $this->users->setTeams($user, $teamIds, $actorId);
    }
}
