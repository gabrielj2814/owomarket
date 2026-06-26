<?php


namespace Src\Authentication\Application\Contracts;

use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\UserEmail;

interface AuthServices {

    /**
     * Consulta un usuario a través de su correo electrónico.
     *
     * @param UserEmail $email El objeto de valor del correo electrónico del usuario.
     * @return User|null La entidad de usuario si se encuentra, o null si no.
     */
    /**
     * Método consultUserByEmail.
     */
    public function consultUserByEmail(UserEmail $email):? User;

}

?>
