<?php

namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\ValueObjects\Uuid;

class DeleteTenantUserByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantUserRepositoryInterface $tenantUserRepository
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $tenantUserId): bool
    {
        $tenantUserUuid = Uuid::make($tenantUserId);

        return $this->tenantUserRepository->deleteTenantUserByUuid($tenantUserUuid);
    }
}
