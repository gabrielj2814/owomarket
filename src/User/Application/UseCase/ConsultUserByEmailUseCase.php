<?php

namespace Src\User\Application\UseCase;

use Src\User\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\User\Application\Data\EmailUserData;
use Src\User\Domain\Entities\User;
use Src\User\Domain\ValueObjects\UserEmail;

class ConsultUserByEmailUseCase {

    public function __construct(
        protected UserRepositoryInterface $userRepository
    ){}

    public function execute(EmailUserData $user): ?User{

        $mail=new UserEmail($user->email);

        $user=$this->userRepository->consultarPorMail($mail);

        return $user;
    }


}


?>
