<?php

namespace App\Application\Teams;

use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Teams\Data\TeamMutationData;
use App\Domain\Teams\Exceptions\TeamNameAlreadyExists;
use App\Models\Team;
use Illuminate\Support\Str;

class SaveTeam
{
    public function __construct(
        private readonly TeamRepository $teams,
        private readonly GetTeam $getTeam,
    ) {}

    public function handle(?int $teamId, TeamMutationData $data): Team
    {
        $team = $teamId === null ? null : $this->getTeam->handle($teamId);

        if ($this->teams->nameExists($data->name, $team?->id)) {
            throw new TeamNameAlreadyExists;
        }

        return $this->teams->save($team, $data, Str::slug($data->name));
    }
}
