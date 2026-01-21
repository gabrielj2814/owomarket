<?php

namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValuesObjects\PhoneNumber;
use Src\Tenant\Domain\ValuesObjects\UserName;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class TenantOwnerUpdatePersonalDataUseCase {




    public function __construct(
        protected TenantOwnerRepositoryInterface $tenant_owner_repository
    ){}


    public function execute(string $_id, string $_name, string $_phone): TenantOwner{
        $id= Uuid::make($_id);
        $name= UserName::make($_name);
        $phone= PhoneNumber::make($_phone);

        $tenantOwner= $this->tenant_owner_repository->consultTenantOwnerByUuid($id);

        $tenantOwner->updatePersonalData($name, $phone);

        $tenantOwnerUpdate = $this->tenant_owner_repository->updatePersonalData($tenantOwner);

        return $tenantOwnerUpdate;
    }

}


?>
