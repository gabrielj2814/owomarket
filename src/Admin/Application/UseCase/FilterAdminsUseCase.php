<?php


namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Shared\Collection\Pagination;

class FilterAdminsUseCase {


    public function __construct(
        protected AdminRepositoryInterface $admin_repository_interface
    ){}


    public function execute(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        bool | null $status,
        int $prePage=50
     ): Pagination{
        return $this->admin_repository_interface->filter($search, $fechaDesdeUTC, $fechaHastaUTC, $status, $prePage);
    }



}


?>
