<?php

namespace App\Application\Users;

use App\Application\Users\Contracts\UserRepository;
use App\Domain\Users\Exceptions\UserNotFound;
use App\Models\User;

class GetUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(int $userId): User
    {
        return $this->users->find($userId) ?? throw new UserNotFound;
    }
}
