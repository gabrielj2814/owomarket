<?php


namespace Src\Authentication\Application\UseCase;

use Exception;
use LogicException;
use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\UserEmail;
use Src\Authentication\Infrastructure\Eloquent\Repositories\UserRepository;

class LoginWebUserUseCase {


    public function __construct(
        protected LoginWebRepositoryInterface $login_web_repository,
        protected UserRepository $user_repository
    ){}

    public function execute(UserEmail $email, string $clave): bool{
        $user=$this->user_repository->consultarPorMail($email);
        try {

            if(!$user){
                throw new Exception("Usuario no encontrado");
            }

            if(!$user->canLogin()){
                throw new Exception("Usuario no autorizado para iniciar sesion");
            }

            if(!$user->getPassword()->verify($clave)){
                throw new LogicException("clave invalida");
            }

            $this->login_web_repository->loginWebUser($email);

            return true;
        } catch (Exception $error) {

            return false;
        }





    }



}



?>
