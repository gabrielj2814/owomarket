<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Shared\Collection\Pagination;

interface TenantRepositoryInterface {


    public function filter(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        string | null $status,
        string | null $request,
        int $prePage=50
    ): Pagination;


}



?>
