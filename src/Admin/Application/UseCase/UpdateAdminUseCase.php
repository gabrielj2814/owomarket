<?php




namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\PhoneNumber;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Admin\Domain\ValueObjects\Uuid;

class UpdateAdminUseCase {



    public function __construct(
        protected AdminRepositoryInterface $admin_repository
    )
    {}


    public function execute(string $uuid, string $name, string $email, string $phone):? Admin{
        $userUuid=Uuid::make($uuid);
        $updateName=UserName::make($name);
        $updateEmail=UserEmail::make($email);
        $updatePhone=PhoneNumber::make($phone);

        $admin= $this->admin_repository->consultByUuid($userUuid);

        if(!$admin){
            return null;
        }


        $admin->setName($updateName);

        $admin->setEmail($updateEmail);

        $admin->setPhone($updatePhone);

        $respuesta=$this->admin_repository->editar($admin);

        if(!$respuesta){
            return null;
        }


        return $admin;

    }


}



?>
