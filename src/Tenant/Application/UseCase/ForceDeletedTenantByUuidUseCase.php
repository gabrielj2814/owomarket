<?php

namespace Src\Tenant\Application\UseCase;

use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;

class ForceDeletedTenantByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantRepositoryInterface $tenant_repository
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $id): bool
    {
        $uuid = Uuid::make($id);

        return $this->tenant_repository->deleteForceTenant($uuid);
    }
}
