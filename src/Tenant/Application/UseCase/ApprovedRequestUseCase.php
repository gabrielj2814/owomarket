<?php


namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class ApprovedRequestUseCase {


    function __construct(
        protected TenantRepositoryInterface $tenantRepository
    ){}

    public function execute(string $uuid): void{
        $uuid= Uuid::make($uuid);

        $tenant= $this->tenantRepository->consultTenantById($uuid);

        if($tenant->isInProgressRequest() == false){
            throw new Exception("el tenant no tiene una solicitud en progreso",400);
        }

        if($tenant == null){
            throw new Exception("el tenant no existe",404);
        }

        $tenant->approvedRequest();

        $this->tenantRepository->changedRequestStatus($tenant);
    }



}

?>
