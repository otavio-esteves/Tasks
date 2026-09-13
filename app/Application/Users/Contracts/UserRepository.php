<?php

namespace App\Application\Users\Contracts;

use App\Application\Users\Data\CreateUserData;
use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepository
{
    /** @return Collection<int, User> */
    public function listForTeam(int $teamId): Collection;

    /** @return Collection<int, User> */
    public function listAll(): Collection;

    /** @return Collection<int, User> */
    public function listPublicAccessCandidates(): Collection;

    public function find(int $userId): ?User;

    public function emailExists(string $email): bool;

    public function create(CreateUserData $data): User;

    /** @param list<int> $userIds */
    public function allBelongToTeam(array $userIds, int $teamId): bool;

    public function countAdministrators(): int;

    public function setAdministrator(User $user, bool $isAdministrator): User;
}
