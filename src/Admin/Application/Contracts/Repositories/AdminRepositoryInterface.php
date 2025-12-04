<?php


namespace Src\Admin\Application\Contracts\Repositories;

use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\Uuid;

interface AdminRepositoryInterface {

    public function create(Admin $admin):? Admin;

    public function consultByUuid(Uuid $uuid):? Admin;

    public function consultByEmail(UserEmail $email):? Admin;

    public function editar(Admin $admin):? Admin;




}



?>
