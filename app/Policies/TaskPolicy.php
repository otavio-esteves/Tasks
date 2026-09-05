<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($team->id);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($task->team_id);
    }

    public function create(User $user, Team $team): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($team->id);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($task->team_id);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->belongsToTeam($task->team_id);
    }
}
