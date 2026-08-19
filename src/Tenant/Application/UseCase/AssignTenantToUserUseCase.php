<?php

namespace Src\Tenant\Application\UseCase;

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValueObjects\RoleTenantUser;

class AssignTenantToUserUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantUserRepositoryInterface $tenantUserRepository,
        protected UuidGenerator $generator
    ) {}

    /**
     * Método execute.
     */
    public function execute(
        string $tenantId, string $userId, string $role, ?array $permissions = null
    ): TenantUser {

        $tenantIdVO = Uuid::make($tenantId);
        $userIdVO = Uuid::make($userId);
        $roleVO = RoleTenantUser::make($role);
        $permissionsVO = $permissions;
        $create_at = CreatedAt::now();

        $tenantUser = TenantUser::create(
            generator: $this->generator,
            tenantId: $tenantIdVO,
            userId: $userIdVO,
            role: $roleVO,
            permissions: $permissionsVO,
            createdAt: $create_at,
        );

        $tenantUser = $this->tenantUserRepository->assignTenantToUser($tenantUser);

        return $tenantUser;

        // lógica para asignar un tenant a un usuario
    }
}
