<?php

namespace Src\Tenant\Application\UseCase;

use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;

class DeleteTenantOwnerByUuidUseCase
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

        return $this->tenantOwnerRepository->deleteTenantOwner($uuid);
    }
}
