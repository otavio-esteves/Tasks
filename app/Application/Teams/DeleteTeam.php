<?php

namespace App\Application\Teams;

use App\Application\Teams\Contracts\TeamRepository;
use App\Domain\Teams\Exceptions\TeamHasActiveDependencies;

class DeleteTeam
{
    public function __construct(
        private readonly GetTeam $getTeam,
        private readonly TeamRepository $teams,
    ) {}

    public function handle(int $teamId): void
    {
        $team = $this->getTeam->handle($teamId);

        if ($this->teams->hasActiveDependencies($team)) {
            throw new TeamHasActiveDependencies;
        }

        $this->teams->delete($team);
    }
}
