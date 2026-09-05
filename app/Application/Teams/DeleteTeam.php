<?php

namespace App\Application\Teams;

use App\Application\Teams\Contracts\TeamRepository;

class DeleteTeam
{
    public function __construct(
        private readonly GetTeam $getTeam,
        private readonly TeamRepository $teams,
    ) {}

    public function handle(int $teamId): void
    {
        $this->teams->delete($this->getTeam->handle($teamId));
    }
}
