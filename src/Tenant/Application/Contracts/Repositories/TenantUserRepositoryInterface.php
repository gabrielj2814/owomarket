<?php

namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Domain\Entities\TenantUser;

interface TenantUserRepositoryInterface
{
    /**
     * Método assignTenantToUser.
     */
    public function assignTenantToUser(TenantUser $tenantUser): TenantUser;

    /**
     * Método consultTenantUsersByUuid.
     */
    public function consultTenantUsersByUuid(Uuid $id): ?TenantUser;

    /**
     * Método consultTenantUsersByUuidTenant.
     */
    public function consultTenantUsersByUuidTenant(Uuid $id): ?TenantUser;

    /**
     * Método consultTenantsUserByUuidTenantOwner.
     */
    public function consultTenantsUserByUuidTenantOwner(Uuid $id): ?array;

    /**
     * Método deleteTenantUserByUuid.
     */
    public function deleteTenantUserByUuid(Uuid $id): bool;
}
