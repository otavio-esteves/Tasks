<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Teams\Data\TeamMutationData;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentTeamRepository implements TeamRepository
{
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return Team::query()
            ->search($search)
            ->withCount('categories')
            ->paginate($perPage);
    }

    public function listOptions(): Collection
    {
        return Team::query()
            ->orderBy('name')
            ->get();
    }

    public function findById(int $teamId): ?Team
    {
        return Team::query()->find($teamId);
    }

    public function nameExists(string $name, ?int $ignoreTeamId = null): bool
    {
        return Team::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($ignoreTeamId !== null, fn ($query) => $query->where('id', '!=', $ignoreTeamId))
            ->exists();
    }

    public function save(?Team $team, TeamMutationData $data, string $slug): Team
    {
        $team ??= new Team;
        $team->fill([
            ...$data->toPersistenceArray(),
            'slug' => $slug,
        ]);
        $team->save();

        return $team->refresh();
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }
}
