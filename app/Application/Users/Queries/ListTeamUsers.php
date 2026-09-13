<?php

namespace App\Application\Users\Queries;

use App\Application\Users\Contracts\UserRepository;
use Illuminate\Support\Collection;

class ListTeamUsers
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(int $teamId): Collection
    {
        return $this->users->listForTeam($teamId);
    }
}
