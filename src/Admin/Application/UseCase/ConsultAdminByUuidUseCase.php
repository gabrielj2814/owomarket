<?php



namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Domain\ValueObjects\Uuid;

class ConsultAdminByUuidUseCase {



    public function __construct(
        protected AdminRepositoryInterface $admin_repository
    )
    {}


    public function execute(Uuid $uuid):? Admin{
        return $this->admin_repository->consultByUuid($uuid);
    }



}




?>
