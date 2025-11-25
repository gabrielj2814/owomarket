<?php


namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\UserEmail;

class ConsultDataUserByEmailCase {

    public function __construct(
        protected UserRepositoryInterface $userRepository
    ){}


    public function execute(UserEmail $mail): ?User{

        $user=$this->userRepository->consultarPorMail($mail);

        return $user;
    }

}


?>
