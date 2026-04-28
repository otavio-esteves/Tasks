<?php

namespace App\Application\Secretariats\Queries;

use App\Application\Secretariats\Contracts\SecretariatRepository;
use Illuminate\Support\Collection;

class ListSecretariatOptions
{
    public function __construct(
        private readonly SecretariatRepository $secretariats,
    ) {}

    public function handle(): Collection
    {
        return $this->secretariats->listOptions();
    }
}
