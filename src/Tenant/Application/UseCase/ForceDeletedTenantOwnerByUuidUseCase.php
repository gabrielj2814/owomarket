<?php

namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\ValueObjects\Uuid;

class ForceDeletedTenantOwnerByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantOwnerRepositoryInterface $tenantOwnerRepository
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $id): bool
    {
        $uuid = Uuid::make($id);

        return $this->tenantOwnerRepository->deleteForceTenantOwner($uuid);
    }
}
