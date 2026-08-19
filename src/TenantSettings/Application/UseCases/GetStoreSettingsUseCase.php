<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\StoreSettings;

final class GetStoreSettingsUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    public function execute(): StoreSettings
    {
        return $this->repository->getStoreSettings();
    }
}
