<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Teams\Data\TeamMutationData;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
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

    public function nameOrSlugExists(string $name, string $slug, ?int $ignoreTeamId = null): bool
    {
        return Team::withTrashed()
            ->where(function ($query) use ($name, $slug): void {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->orWhere('slug', $slug);
            })
            ->when($ignoreTeamId !== null, fn ($query) => $query->where('id', '!=', $ignoreTeamId))
            ->exists();
    }

    public function hasActiveDependencies(Team $team): bool
    {
        return User::query()->where('team_id', $team->id)->exists()
            || Category::query()->where('team_id', $team->id)->exists()
            || Task::query()->where('team_id', $team->id)->exists();
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
