<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\Exceptions\SettingNotFoundException;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;

final class GetSettingByKeyUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    public function execute(string $key): TenantSetting
    {
        $settingKey = new SettingKey($key);
        $setting = $this->repository->findByKey($settingKey);

        if ($setting === null) {
            throw SettingNotFoundException::forKey($key);
        }

        return $setting;
    }
}
