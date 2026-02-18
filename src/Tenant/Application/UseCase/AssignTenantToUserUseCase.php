<?php

namespace Src\Tenant\Application\UseCase;

use Src\Shared\ValuesObjects\CreatedAt;
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
        $create_at=CreatedAt::now();

        $tenantUser = TenantUser::create(
            tenantId: $tenantIdVO,
            userId: $userIdVO,
            role: $roleVO,
            permissions: $permissionsVO,
            createdAt: $create_at,
        );

        $tenantUser= $this->tenantUserRepository->assignTenantToUser($tenantUser);

        return $tenantUser;

        // lógica para asignar un tenant a un usuario
    }



}


?>
