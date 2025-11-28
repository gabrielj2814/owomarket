<?php


namespace Src\Authentication\Application\UseCase;

use Exception;
use LogicException;
use Src\Authentication\Application\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;
use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\UserEmail;

class LoginApiUserUseCase {

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected PersonalAccessTokenRepositoryInterface $personal_access_token_repository
        ){}


    public function execute(UserEmail $email, string $clave):? string{

        try {
            $user=$this->userRepository->consultarPorMail($email);

            if(!$user){
                throw new LogicException("Usuario no encontrado");
            }

            if(!$user->canLogin()){
                throw new Exception("Usuario no autorizado para iniciar sesion");
            }

            if(!$user->getPassword()->verify($clave)){
                throw new LogicException("clave invalida");
            }

            $token = $this->personal_access_token_repository->generarToken($user);

            return $token;
        } catch (Exception $error) {
            return null;
        }
    }




}


?>
