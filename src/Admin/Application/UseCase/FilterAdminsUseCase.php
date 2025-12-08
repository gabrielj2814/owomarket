<?php


namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Shared\Collection\Pagination;

class FilterAdminsUseCase {


    public function __construct(
        protected AdminRepositoryInterface $admin_repository_interface
    ){}


    public function excute(string | null $search, int $prePage=50): Pagination{
        return $this->admin_repository_interface->filter($search, $prePage);
    }



}


?>
