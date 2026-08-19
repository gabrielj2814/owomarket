<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\DTOs\SaveSettingData;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\TenantSetting;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;

final class SaveSettingUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    public function execute(SaveSettingData $dto): TenantSetting
    {
        $settingKey = new SettingKey($dto->key);
        $settingType = new SettingType($dto->type);
        $settingGroup = new SettingGroup($dto->group);

        $existing = $this->repository->findByKey($settingKey);

        if ($existing !== null) {
            $existing->updateValue(
                value: $dto->value,
                type: $settingType,
                group: $settingGroup
            );
            $this->repository->save($existing);

            return $existing;
        }

        $newSetting = TenantSetting::create(
            key: $settingKey,
            value: $dto->value,
            type: $settingType,
            group: $settingGroup
        );

        $this->repository->save($newSetting);

        return $newSetting;
    }
}
