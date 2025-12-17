<?php
declare(strict_types=1);


namespace Src\Tenant\Application\UseCase;

use Src\Shared\Collection\Pagination;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;

class FilterTenantUseCase {

    public function __construct(
        protected TenantRepositoryInterface $tenant_repository_interface
    ){}

    public function execute(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        string | null $status,
        string | null $request,
        int $prePage=50
    ): Pagination{
        return $this->tenant_repository_interface->filter($search, $fechaDesdeUTC, $fechaHastaUTC, $status, $request, $prePage);
    }



}




?>
