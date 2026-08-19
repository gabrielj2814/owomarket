<?php

namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValueObjects\Uuid;

interface TenantOwnerRepositoryInterface
{
    /**
     * Método createTenantOwner.
     */
    public function createTenantOwner(TenantOwner $tenantOwner): TenantOwner;

    /**
     * Método deleteTenantOwner.
     */
    public function deleteTenantOwner(Uuid $id): bool;

    /**
     * Método deleteForceTenantOwner.
     */
    public function deleteForceTenantOwner(Uuid $id): bool;

    /**
     * Método consultTenantOwnerByUuid.
     */
    public function consultTenantOwnerByUuid(Uuid $id): TenantOwner;

    /**
     * Método updatePersonalData.
     */
    public function updatePersonalData(TenantOwner $tenantOwner): TenantOwner;

    /**
     * Método updatePassword.
     */
    public function updatePassword(TenantOwner $tenantOwner): TenantOwner;
}
