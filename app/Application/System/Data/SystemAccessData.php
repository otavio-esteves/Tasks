<?php

namespace App\Application\System\Data;

final readonly class SystemAccessData
{
    public function __construct(
        public bool $loginRequired,
        public ?int $publicUserId,
    ) {}
}
