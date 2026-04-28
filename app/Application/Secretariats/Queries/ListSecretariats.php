<?php

namespace App\Application\Secretariats\Queries;

use App\Application\Secretariats\Contracts\SecretariatRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSecretariats
{
    public function __construct(
        private readonly SecretariatRepository $secretariats,
    ) {}

    public function handle(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return $this->secretariats->paginate($search, $perPage);
    }
}
