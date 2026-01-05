<?php


namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class ActiveTenantByUuidUseCase {

    public function __construct(
        protected TenantRepositoryInterface $tenant_repository
    ){}

    public function execute(string $uuid): Tenant{
        $uuid= Uuid::make($uuid);
        $tenant=$this->tenant_repository->consultTenantById($uuid);
        if(!$tenant){
            throw new Exception("No pudo activar el tenant por que no se encotro en la DB",404);
        }
        $this->tenant_repository->active($tenant);
        return $tenant;
    }

}



?>
