<?php

namespace App\Application\Teams;

use App\Application\Teams\Contracts\TeamRepository;
use App\Domain\Teams\Exceptions\TeamNotFound;
use App\Models\Team;

class GetTeam
{
    public function __construct(
        private readonly TeamRepository $teams,
    ) {}

    public function handle(int $teamId): Team
    {
        $team = $this->teams->findById($teamId);

        if (! $team) {
            throw new TeamNotFound;
        }

        return $team;
    }
}
