<?php

namespace App\Application\Users\Queries;

use App\Application\Users\Contracts\UserRepository;
use Illuminate\Support\Collection;

class ListPublicAccessCandidates
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(): Collection
    {
        return $this->users->listPublicAccessCandidates();
    }
}
