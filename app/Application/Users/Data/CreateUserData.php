<?php

namespace App\Application\Users\Data;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?int $teamId,
        public bool $isAdministrator,
    ) {}

    /** @param array{name:string,email:string,password:string,team_id:int|string|null,is_admin:bool} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name']),
            email: mb_strtolower(trim($data['email'])),
            password: $data['password'],
            teamId: ($data['team_id'] ?? null) !== null && $data['team_id'] !== '' ? (int) $data['team_id'] : null,
            isAdministrator: $data['is_admin'],
        );
    }
}
