<?php


namespace Src\Admin\Application\Contracts\Repositories;

use Src\Admin\Domain\Etities\Admin;

interface AdminRepositoryInterface {

    public function create(Admin $admin):? Admin;


}



?>
