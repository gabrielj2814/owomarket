<?php



namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Shared\Collection\Pagination;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class ConsultTenantsByUuidOfOwnerUseCase {


    public function __construct(
        protected TenantRepositoryInterface $tenant_repository,
        protected TenantUserRepositoryInterface $tenant_user_repository
    ){}


    public function execute(string $_uuid_owner_tenant, int $prePage=50): Pagination{
        $uuidOwner= Uuid::make($_uuid_owner_tenant);
        $tenant_user= $this->tenant_user_repository->consultTenantsUserByUuidTenantOwner($uuidOwner);
        if(!$tenant_user){

            throw new Exception('Tenant User not found',404);
        }

        return $this->tenant_repository->consultTenantsByIdOwnerPaginate($uuidOwner, $prePage);
    }




}



?>
