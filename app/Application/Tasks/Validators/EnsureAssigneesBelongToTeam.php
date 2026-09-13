<?php

namespace App\Application\Tasks\Validators;

use App\Application\Users\Contracts\UserRepository;
use App\Domain\Tasks\Exceptions\InvalidTaskAssignees;

class EnsureAssigneesBelongToTeam
{
    public function __construct(private readonly UserRepository $users) {}

    /** @param list<int> $assigneeIds */
    public function handle(int $teamId, array $assigneeIds): void
    {
        if (! $this->users->allBelongToTeam($assigneeIds, $teamId)) {
            throw new InvalidTaskAssignees;
        }
    }
}
