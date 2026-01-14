<?php


namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class RejectedRequestUseCase {


    function __construct(
        protected TenantRepositoryInterface $tenantRepository
    ){}

    public function execute(string $uuid): Tenant{
        $uuid= Uuid::make($uuid);

        $tenant= $this->tenantRepository->consultTenantById($uuid);

        if($tenant->isInProgressRequest() == false){
            throw new Exception("el tenant no tiene una solicitud en progreso",400);
        }

        if($tenant == null){
            throw new Exception("el tenant no existe",404);
        }

        $tenant->rejectedRequest();
        $tenant->inactive();

        $this->tenantRepository->inactive($tenant);
        $this->tenantRepository->changedRequestStatus($tenant);

        return $tenant;
    }



}

?>
