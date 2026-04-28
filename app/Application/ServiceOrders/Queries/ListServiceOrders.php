<?php

namespace App\Application\ServiceOrders\Queries;

use App\Application\ServiceOrders\Contracts\ServiceOrderRepository;
use App\Application\ServiceOrders\Data\ServiceOrderListResult;

class ListServiceOrders
{
    public function __construct(
        private readonly ServiceOrderRepository $serviceOrders,
    ) {}

    /**
     * @param  array{category_id?:int|null,status?:string|null,urgent?:bool|null,quick_filter?:string|null}  $filters
     */
    public function handle(int $secretariatId, string $search = '', array $filters = [], int $perPage = 15): ServiceOrderListResult
    {
        return $this->serviceOrders->listForSecretariat($secretariatId, $search, $filters, $perPage);
    }
}
