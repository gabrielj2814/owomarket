<?php

namespace Src\Tenant\Application\UseCase;


use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValuesObjects\RoleTenantUser;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class AssignTenantToUserUseCase {


    public function __construct(
        protected TenantUserRepositoryInterface $tenantUserRepository
    ) {}


    public function execute(
        string $tenantId, string $userId, string $role, ?array $permissions = null
    ): TenantUser {

        $tenantIdVO = Uuid::make($tenantId);
        $userIdVO = Uuid::make($userId);
        $roleVO = RoleTenantUser::make($role);
        $permissionsVO = $permissions;

        $tenantUser = TenantUser::create(
            tenantId: $tenantIdVO,
            userId: $userIdVO,
            role: $roleVO,
            permissions: $permissionsVO
        );

        $tenantUser= $this->tenantUserRepository->assignTenantToUser($tenantUser);

        return $tenantUser;

        // lógica para asignar un tenant a un usuario
    }



}


?>
