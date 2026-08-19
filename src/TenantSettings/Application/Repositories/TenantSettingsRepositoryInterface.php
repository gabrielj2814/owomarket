<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\Repositories;

use Src\TenantSettings\Domain\Entities\StoreSettings;
use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingId;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;

interface TenantSettingsRepositoryInterface
{
    public function save(TenantSetting $setting): void;

    /**
     * @param  TenantSetting[]  $settings
     */
    public function saveMultiple(array $settings): void;

    public function findByKey(SettingKey $key): ?TenantSetting;

    public function findById(SettingId $id): ?TenantSetting;

    /**
     * @return TenantSetting[]
     */
    public function listByGroup(SettingGroup $group): array;

    /**
     * @return TenantSetting[]
     */
    public function listAll(): array;

    public function delete(SettingKey $key): void;

    public function getStoreSettings(): StoreSettings;

    public function updateStoreSettings(StoreSettings $settings): void;
}
