<?php

namespace App\Application\ServiceOrders;

use App\Application\ServiceOrders\Contracts\ServiceOrderRepository;
use App\Application\ServiceOrders\Data\CreateServiceOrderData;
use App\Application\ServiceOrders\Validators\EnsureCategoryBelongsToSecretariat;
use App\Models\ServiceOrder;

class CreateServiceOrder
{
    public function __construct(
        private readonly EnsureCategoryBelongsToSecretariat $ensureCategoryBelongsToSecretariat,
        private readonly ServiceOrderRepository $serviceOrders,
    ) {}

    public function handle(int $secretariatId, int $userId, CreateServiceOrderData $data): ServiceOrder
    {
        $this->ensureCategoryBelongsToSecretariat->handle($secretariatId, $data->categoryId);

        return $this->serviceOrders->createForSecretariat($secretariatId, $userId, $data);
    }
}
