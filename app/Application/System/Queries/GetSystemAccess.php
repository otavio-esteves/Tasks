<?php

namespace App\Application\System\Queries;

use App\Application\System\Contracts\SystemSettingRepository;
use App\Application\System\Data\SystemAccessData;

class GetSystemAccess
{
    public function __construct(private readonly SystemSettingRepository $settings) {}

    public function handle(): SystemAccessData
    {
        return $this->settings->getAccess();
    }
}
