<?php


namespace Src\Tenant\Application\UseCase;

use Src\Shared\Collection\Pagination;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;

class FilterTenantUseCase {

    public function __construct(
        protected TenantRepositoryInterface $tenant_repository_interface
    ){}

    public function execute(): Pagination{
        return $this->tenant_repository_interface->filter();
    }



}




?>
