<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValuesObjects\Uuid;

interface TenantUserRepositoryInterface {


    public function assignTenantToUser(TenantUser $tenantUser): TenantUser;
    public function consultTenantUsersByUuid(Uuid $id): ?TenantUser;
    public function deleteTenantUserByUuid(Uuid $id): bool;


}



?>
