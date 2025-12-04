<?php


namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Domain\ValueObjects\UserEmail;

class ConsultAdminByEmailUseCase {


    public function __construct(
        protected AdminRepositoryInterface $admin_repository
    )
    {}


    public function execute(UserEmail $email): ?Admin{
        return $this->admin_repository->consultByEmail($email);
    }



}


?>
