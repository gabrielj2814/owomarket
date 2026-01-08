<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Tenant\Domain\Entities\TenantOwner;

interface TenantOwnerRepositoryInterface {

    public function createTenantOwner(TenantOwner $tenantOwner): TenantOwner;

}



?>
