<?php

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;

class InactiveTenantByUuidUseCase
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
    public function execute(string $uuid): Tenant
    {
        $uuid = Uuid::make($uuid);
        $tenant = $this->tenant_repository->consultTenantById($uuid);
        if (! $tenant) {
            throw new Exception('No pudo desactivar el tenant por que no se encontro en la DB', 404);
        }
        if ($tenant->getStatus()->isSuspended() && $tenant->getRequest()->isApproved()) {
            $this->tenant_repository->inactive($tenant);
        } else {
            throw new Exception('No se puede inactivar un tenant que no este en estado suspendido', 400);
        }

        return $tenant;
    }
}
