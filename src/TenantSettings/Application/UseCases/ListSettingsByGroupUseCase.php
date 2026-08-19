<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;

final class ListSettingsByGroupUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    /**
     * @return TenantSetting[]
     */
    public function execute(string $group): array
    {
        $settingGroup = new SettingGroup($group);

        return $this->repository->listByGroup($settingGroup);
    }
}
