<?php


namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Admin\Domain\ValueObjects\Uuid;

class ChangeStatuAdminByUuidUseCase {


    public function __construct(
        protected AdminRepositoryInterface $admin_repository
    ){}


    public function execute(Uuid $uuid, UserStatus $statu): void{
        $this->admin_repository->changeStatu($uuid, $statu);
    }



}




?>
