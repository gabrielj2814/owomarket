<?php


namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class DeleteTenantOwnerByUuidUseCase {



    public function __construct(
        protected TenantOwnerRepositoryInterface $tenantOwnerRepository
    ){}

    public function execute(string $id): bool {
        $uuid= Uuid::make($id);
        return $this->tenantOwnerRepository->deleteTenantOwner($uuid);
    }




}



?>
