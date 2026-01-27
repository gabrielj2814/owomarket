<?php


namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class CancelAccountTenantOwnerUseCase {



    public function __construct(
        protected TenantOwnerRepositoryInterface $tenant_owner_repository_interface,
        protected TenantRepositoryInterface $tenant_repository_interface,
        protected TenantUserRepositoryInterface $tenant_user_repository_interface
    ){}

    public function execute(string $_id): void{
        $id= Uuid::make($_id);



        // $tenantOwner=$this->tenant_owner_repository_interface->consultTenantOwnerByUuid($id);
        // $tenant=$this->tenant_repository_interface->consultTenantById($tenanUser->getTenantId());

        $tenanUser=$this->tenant_user_repository_interface->consultTenantUsersByUuidTenantOwner($id);
        if(!$tenanUser){
            throw new Exception('Tenant User not found',404);
        }
        // dd('stop');

        $this->tenant_repository_interface->deleteTenant($tenanUser->getTenantId());
        // $this->tenant_repository_interface->deleteForceTenant($tenanUser->getTenantId());

        $this->tenant_owner_repository_interface->deleteTenantOwner($tenanUser->getUserId());
        // $this->tenant_owner_repository_interface->deleteForceTenantOwner($tenanUser->getUserId());

    }



}




?>
