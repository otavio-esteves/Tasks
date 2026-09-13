<?php

namespace App\Application\Auth;

use App\Application\Auth\Data\RedirectTargetData;
use App\Application\Teams\Queries\ListTeamOptions;
use App\Models\Team;
use App\Models\User;

class ResolveUserHomeRoute
{
    public function __construct(private readonly ListTeamOptions $listTeamOptions) {}

    public function handle(User $user): RedirectTargetData
    {
        if ($user->team_id !== null) {
            return new RedirectTargetData(
                routeName: 'teams.tasks',
                parameters: ['team' => $user->team_id],
            );
        }

        if ($user->isAdmin()) {
            $team = $this->listTeamOptions->handle()->first();

            if ($team instanceof Team) {
                return new RedirectTargetData(
                    routeName: 'teams.tasks',
                    parameters: ['team' => $team->id],
                );
            }

            return new RedirectTargetData('admin.teams');
        }

        return new RedirectTargetData('access.pending');
    }
}
