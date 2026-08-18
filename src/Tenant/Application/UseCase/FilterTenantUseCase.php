<?php

declare(strict_types=1);

namespace Src\Tenant\Application\UseCase;

use Src\Shared\Collection\Pagination;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;

class FilterTenantUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantRepositoryInterface $tenant_repository_interface
    ) {}

    /**
     * Método execute.
     */
    public function execute(
        ?string $search,
        ?string $fechaDesdeUTC,
        ?string $fechaHastaUTC,
        ?string $status,
        ?string $request,
        int $prePage = 50
    ): Pagination {
        return $this->tenant_repository_interface->filter($search, $fechaDesdeUTC, $fechaHastaUTC, $status, $request, $prePage);
    }
}
