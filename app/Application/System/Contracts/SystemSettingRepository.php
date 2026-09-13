<?php

namespace App\Application\System\Contracts;

use App\Application\System\Data\SystemAccessData;

interface SystemSettingRepository
{
    public function getAccess(): SystemAccessData;

    public function saveAccess(SystemAccessData $data): SystemAccessData;
}
