<?php

namespace Src\Tenant\Application\UseCase;

use Src\Shared\Domain\ValueObjects\PhoneNumber;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;

class TenantOwnerUpdatePersonalDataUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantOwnerRepositoryInterface $tenant_owner_repository
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $_id, string $_name, string $_phone): TenantOwner
    {
        $id = Uuid::make($_id);
        $name = UserName::make($_name);
        $phone = PhoneNumber::make($_phone);

        $tenantOwner = $this->tenant_owner_repository->consultTenantOwnerByUuid($id);

        $tenantOwner->updatePersonalData($name, $phone);

        $tenantOwnerUpdate = $this->tenant_owner_repository->updatePersonalData($tenantOwner);

        return $tenantOwnerUpdate;
    }
}
