<?php
declare(strict_types=1);


namespace Src\Tenant\Application\UseCase;

use Src\Shared\Collection\Pagination;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class ConsultTenantByUuidUseCase {

    public function __construct(
        protected TenantRepositoryInterface $tenant_repository_interface
    ){}

    public function execute(Uuid $uuid):? Tenant{
        return $this->tenant_repository_interface->consultTenantById($uuid);
    }



}


?>
