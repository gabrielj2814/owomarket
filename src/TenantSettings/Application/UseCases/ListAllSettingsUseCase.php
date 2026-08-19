<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\TenantSetting;

final class ListAllSettingsUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    /**
     * @return TenantSetting[]
     */
    public function execute(): array
    {
        return $this->repository->listAll();
    }
}
