<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Users\Contracts\UserRepository;
use App\Application\Users\Data\CreateUserData;
use App\Domain\Users\Exceptions\LastAdministratorRequired;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentUserRepository implements UserRepository
{
    public function listForTeam(int $teamId): Collection
    {
        return User::query()
            ->where(fn ($query) => $query->where('team_id', $teamId)
                ->orWhereHas('additionalTeams', fn ($teams) => $teams->whereKey($teamId)))
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'team_id', 'is_admin']);
    }

    public function listAll(): Collection
    {
        return User::query()
            ->with(['team:id,name', 'additionalTeams:id,name'])
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function listPublicAccessCandidates(): Collection
    {
        return User::query()
            ->with('team:id,name')
            ->where('is_admin', false)
            ->whereNotNull('team_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function find(int $userId): ?User
    {
        return User::query()->with('additionalTeams:id,name')->find($userId);
    }

    public function emailExists(string $email): bool
    {
        return User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->exists();
    }

    public function create(CreateUserData $data): User
    {
        $user = new User;
        $user->fill([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'team_id' => $data->teamId,
        ]);
        $user->forceFill([
            'is_admin' => $data->isAdministrator,
        ])->save();

        return $user->refresh();
    }

    public function allBelongToTeam(array $userIds, int $teamId): bool
    {
        $ids = array_values(array_unique($userIds));

        if ($ids === []) {
            return true;
        }

        return User::query()
            ->whereIn('id', $ids)
            ->where(fn ($query) => $query->where('team_id', $teamId)
                ->orWhereHas('additionalTeams', fn ($teams) => $teams->whereKey($teamId)))
            ->count() === count($ids);
    }

    public function countAdministrators(): int
    {
        return User::query()->where('is_admin', true)->count();
    }

    public function setAdministrator(User $user, bool $isAdministrator): User
    {
        return DB::transaction(function () use ($user, $isAdministrator): User {
            User::query()->where('is_admin', true)->lockForUpdate()->get(['id']);

            if (! $isAdministrator && $user->isAdmin() && $this->countAdministrators() <= 1) {
                throw new LastAdministratorRequired;
            }

            $user->forceFill(['is_admin' => $isAdministrator])->save();

            return $user->refresh();
        });
    }

    public function setTeams(User $user, array $teamIds, int $actorId): User
    {
        return DB::transaction(function () use ($user, $teamIds, $actorId): User {
            $primaryTeamId = in_array($user->team_id, $teamIds, true) ? $user->team_id : ($teamIds[0] ?? null);
            $additionalTeamIds = array_values(array_diff($teamIds, [$primaryTeamId]));

            $tasks = Task::withTrashed()
                ->whereHas('assignees', fn ($query) => $query->whereKey($user->id))
                ->whereNotIn('tasks.team_id', $teamIds)
                ->with('assignees:id')
                ->get();

            foreach ($tasks as $task) {
                $previousAssigneeIds = $task->assignees->pluck('id')->map(fn ($id): int => (int) $id)->all();
                $task->assignees()->detach($user->id);
                $task->histories()->create([
                    'description' => 'Responsável removido após mudança de equipe.',
                    'user_id' => $actorId,
                    'metadata' => [
                        'assignee_ids' => [
                            'from' => $previousAssigneeIds,
                            'to' => array_values(array_diff($previousAssigneeIds, [$user->id])),
                        ],
                    ],
                ]);
            }

            $user->team_id = $primaryTeamId;
            $user->save();
            $user->additionalTeams()->sync($additionalTeamIds);

            return $user->refresh();
        });
    }
}
