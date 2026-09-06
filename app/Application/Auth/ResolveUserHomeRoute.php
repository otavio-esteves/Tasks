<?php

namespace App\Application\Auth;

use App\Application\Auth\Data\RedirectTargetData;
use App\Models\User;

class ResolveUserHomeRoute
{
    public function handle(User $user): RedirectTargetData
    {
        if ($user->team_id !== null) {
            return new RedirectTargetData(
                routeName: 'teams.tasks',
                parameters: ['team' => $user->team_id],
            );
        }

        return new RedirectTargetData($user->isAdmin() ? 'dashboard' : 'access.pending');
    }
}
