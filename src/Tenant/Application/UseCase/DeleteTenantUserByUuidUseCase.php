<?php


namespace Src\Tenant\Application\UseCase;

use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Uuid;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantUser;

class DeleteTenantUserByUuidUseCase {


    public function __construct(
        protected TenantUserRepositoryInterface $tenantUserRepository
    ){}

    public function execute(string $tenantUserId): bool {
        $tenantUserUuid = Uuid::make($tenantUserId);
        return $this->tenantUserRepository->deleteTenantUserByUuid($tenantUserUuid);
    }



}







?>
