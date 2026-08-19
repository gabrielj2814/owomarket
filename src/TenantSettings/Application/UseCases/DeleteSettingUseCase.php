<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;

final class DeleteSettingUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    public function execute(string $key): void
    {
        $settingKey = new SettingKey($key);
        $this->repository->delete($settingKey);
    }
}
