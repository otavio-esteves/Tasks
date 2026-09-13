<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\System\Contracts\SystemSettingRepository;
use App\Application\System\Data\SystemAccessData;
use App\Models\SystemSetting;

class EloquentSystemSettingRepository implements SystemSettingRepository
{
    public function getAccess(): SystemAccessData
    {
        $setting = SystemSetting::query()->firstOrNew();

        if (! $setting->exists) {
            return new SystemAccessData(loginRequired: true, publicUserId: null);
        }

        return new SystemAccessData(
            loginRequired: (bool) $setting->login_required,
            publicUserId: $setting->public_user_id,
        );
    }

    public function saveAccess(SystemAccessData $data): SystemAccessData
    {
        $setting = SystemSetting::query()->firstOrNew();
        $setting->fill([
            'login_required' => $data->loginRequired,
            'public_user_id' => $data->publicUserId,
        ])->save();

        return $this->getAccess();
    }
}
