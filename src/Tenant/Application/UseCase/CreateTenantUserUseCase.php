<?php


namespace Src\Tenant\Application\UseCase;

use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValueObjects\RoleTenantUser;
use Src\Tenant\Domain\ValueObjects\Uuid;

class CreateTenantUserUseCase {


    /**
     * Constructor de la clase.
     */


    public function __construct(
        protected TenantUserRepositoryInterface $tenant_user_repository
    ){}

    /**
     * Método execute.
     */

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
