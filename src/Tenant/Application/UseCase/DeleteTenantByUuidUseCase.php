<?php

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;

class DeleteTenantByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantRepositoryInterface $tenant_repository,
        protected TenantUserRepositoryInterface $tenant_user_repository,
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $idTenant, string $idTenantOwner): bool
    {
        $uuidTenantOwner = Uuid::make($idTenantOwner);
        $uuidTenant = Uuid::make($idTenant);

        $tenantUser = $this->tenant_user_repository->consultTenantUsersByUuidTenant($uuidTenant);
        if (! $tenantUser || $tenantUser->getUserId()->value() !== $uuidTenantOwner->value()) {
            throw new Exception('Tenant User not found or Tenant Owner mismatch', 404);
        }

        return $this->tenant_repository->deleteTenant($uuidTenant);
    }
}
