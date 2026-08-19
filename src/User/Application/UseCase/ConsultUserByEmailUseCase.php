<?php

namespace Src\User\Application\UseCase;

use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\User\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\User\Application\Data\EmailUserData;
use Src\User\Domain\Entities\User;

class ConsultUserByEmailUseCase
{
    /**
     * Constructor del caso de uso.
     *
     * @param  UserRepositoryInterface  $userRepository  Repositorio de usuarios.
     */
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Ejecuta el caso de uso para consultar un usuario por su email.
     *
     * @param  EmailUserData  $user  Datos que contienen el email a buscar.
     * @return User|null La entidad de usuario si se encuentra, null en caso contrario.
     */
    /**
     * Método execute.
     */
    public function execute(EmailUserData $user): ?User
    {

        $mail = UserEmail::make($user->email);

        $user = $this->userRepository->consultarPorMail($mail);

        return $user;
    }
}
