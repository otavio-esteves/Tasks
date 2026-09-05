<?php

namespace App\Application\Teams\Queries;

use App\Application\Teams\Contracts\TeamRepository;
use Illuminate\Support\Collection;

class ListTeamOptions
{
    public function __construct(
        private readonly TeamRepository $teams,
    ) {}

    public function handle(): Collection
    {
        return $this->teams->listOptions();
    }
}
