<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Users\Contracts\UserRepository;
use App\Application\Users\Data\CreateUserData;
use App\Domain\Users\Exceptions\LastAdministratorRequired;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentUserRepository implements UserRepository
{
    public function listForTeam(int $teamId): Collection
    {
        return User::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'team_id', 'is_admin']);
    }

    public function listAll(): Collection
    {
        return User::query()
            ->with('team:id,name')
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
        return User::query()->find($userId);
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
            ->where('team_id', $teamId)
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
}
