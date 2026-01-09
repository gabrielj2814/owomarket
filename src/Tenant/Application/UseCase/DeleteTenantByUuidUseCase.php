<?php


namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class DeleteTenantByUuidUseCase {



    public function __construct(
        protected TenantRepositoryInterface $tenant_repository
    ){}

    public function execute(string $id): bool {
        $uuid= Uuid::make($id);
        return $this->tenant_repository->deleteTenant($uuid);
    }




}



?>
