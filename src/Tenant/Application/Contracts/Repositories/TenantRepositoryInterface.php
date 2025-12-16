<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Shared\Collection\Pagination;

interface TenantRepositoryInterface {


    public function filter(
        // string | null $search,
        // string | null $fechaDesdeUTC,
        // string | null $fechaHastaUTC,
        // bool | null $status,
        // int $prePage=50
    ): Pagination;


}



?>
