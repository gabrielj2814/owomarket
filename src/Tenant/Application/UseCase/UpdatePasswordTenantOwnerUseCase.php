<?php

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValueObjects\Password;
use Src\Tenant\Domain\ValueObjects\Uuid;

class UpdatePasswordTenantOwnerUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantOwnerRepositoryInterface $tenant_owner_repository,
        protected PasswordValidator $password_validator,
        protected PasswordHasher $password_hasher
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $_id, string $_claveVieja, string $_claveNueva): TenantOwner
    {
        $id = Uuid::make($_id);

        $tenantOwner = $this->tenant_owner_repository->consultTenantOwnerByUuid($id);

        if (! $tenantOwner) {
            throw new Exception('el tenant owner no fue encontrado en la base de datos para poder actualizar la clave', 404);
        }

        if (! $tenantOwner->getPassword()->verify($_claveVieja, $this->password_hasher)) {
            throw new Exception('Clave invalidad', 400);
        }

        $password = Password::fromPlainText($_claveNueva, $this->password_validator, $this->password_hasher);

        $tenantOwner->setPassword($password);

        $this->tenant_owner_repository->updatePassword($tenantOwner);

        return $tenantOwner;

    }
}
