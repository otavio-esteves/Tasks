<?php

namespace App\Application\ServiceOrders;

use App\Application\ServiceOrders\Contracts\ServiceOrderRepository;
use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Models\ServiceOrder;

class ChangeServiceOrderStatus
{
    public function __construct(
        private readonly GetServiceOrder $getServiceOrder,
        private readonly ServiceOrderRepository $serviceOrders,
    ) {}

    public function handle(int $secretariatId, int $userId, int $serviceOrderId, ServiceOrderStatus $status): ServiceOrder
    {
        $serviceOrder = $this->getServiceOrder->handle($secretariatId, $serviceOrderId);

        return $this->serviceOrders->changeStatus($serviceOrder, $userId, $status);
    }
}
