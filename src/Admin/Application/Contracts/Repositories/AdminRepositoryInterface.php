<?php


namespace Src\Admin\Application\Contracts\Repositories;

use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Shared\Collection\Pagination;

interface AdminRepositoryInterface {

    public function create(Admin $admin):? Admin;

    public function consultByUuid(Uuid $uuid):? Admin;

    public function consultByEmail(UserEmail $email):? Admin;

    public function editar(Admin $admin):? Admin;

    public function filter(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        bool | null $status,
        int $prePage=50
    ): Pagination;

    public function eliminar(Uuid $uuid): void;



}



?>
