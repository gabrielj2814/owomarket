<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValuesObjects\Password;
use Src\Tenant\Domain\ValuesObjects\Uuid;

interface TenantOwnerRepositoryInterface {

    public function createTenantOwner(TenantOwner $tenantOwner): TenantOwner;

    public function deleteTenantOwner(Uuid $id): bool;

    public function deleteForceTenantOwner(Uuid $id): bool;

    public function consultTenantOwnerByUuid(Uuid $id): TenantOwner;

    public function updatePersonalData(TenantOwner $tenantOwner) :TenantOwner;

    public function updatePassword(TenantOwner $tenantOwner) :TenantOwner;

}



?>
