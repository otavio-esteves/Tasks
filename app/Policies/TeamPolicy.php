<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($team->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->isAdmin();
    }
}
