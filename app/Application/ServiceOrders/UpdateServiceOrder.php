<?php

namespace App\Application\ServiceOrders;

use App\Application\ServiceOrders\Contracts\ServiceOrderRepository;
use App\Application\ServiceOrders\Data\UpdateServiceOrderData;
use App\Application\ServiceOrders\Validators\EnsureCategoryBelongsToSecretariat;
use App\Models\ServiceOrder;

class UpdateServiceOrder
{
    public function __construct(
        private readonly EnsureCategoryBelongsToSecretariat $ensureCategoryBelongsToSecretariat,
        private readonly GetServiceOrder $getServiceOrder,
        private readonly ServiceOrderRepository $serviceOrders,
    ) {}

    public function handle(int $secretariatId, int $userId, int $serviceOrderId, UpdateServiceOrderData $data): ServiceOrder
    {
        $this->ensureCategoryBelongsToSecretariat->handle($secretariatId, $data->categoryId);

        $serviceOrder = $this->getServiceOrder->handle($secretariatId, $serviceOrderId);

        return $this->serviceOrders->update($serviceOrder, $userId, $data);
    }
}
