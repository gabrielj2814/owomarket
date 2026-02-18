<?php


namespace Src\Tenant\Application\UseCase;

use Src\Shared\ValuesObjects\CreatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValuesObjects\RoleTenantUser;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class CreateTenantUserUseCase {


    public function __construct(
        protected TenantUserRepositoryInterface $tenant_user_repository
    ){}

    public function execute(string $_uuid_owner, string $_uuid_tenant): TenantUser{
        $uuid_owner= Uuid::make($_uuid_owner);
        $uuid_tenant= Uuid::make($_uuid_tenant);
        $role= RoleTenantUser::owner();
        $permisos= null;
        $create_at=CreatedAt::now();
        $tenantUser= TenantUser::create(
            $uuid_tenant,
            $uuid_owner,
            $role,
            $permisos,
            $create_at,
        );
        return $this->tenant_user_repository->assignTenantToUser($tenantUser);
    }


}


?>
