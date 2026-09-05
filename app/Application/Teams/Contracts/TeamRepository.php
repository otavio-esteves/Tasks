<?php

namespace App\Application\Teams\Contracts;

use App\Application\Teams\Data\TeamMutationData;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TeamRepository
{
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator;

    public function listOptions(): Collection;

    public function findById(int $teamId): ?Team;

    public function nameExists(string $name, ?int $ignoreTeamId = null): bool;

    public function save(?Team $team, TeamMutationData $data, string $slug): Team;

    public function delete(Team $team): void;
}
