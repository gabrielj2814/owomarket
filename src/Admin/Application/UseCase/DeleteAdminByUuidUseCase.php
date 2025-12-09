<?php


namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\ValueObjects\Uuid;

class DeleteAdminByUuidUseCase {


    public function __construct(
        protected AdminRepositoryInterface $admin_repository_interface
    ){}

    public function execute(Uuid $uuid): void{
        $this->admin_repository_interface->eliminar($uuid);
    }



}



?>
