<?php

namespace App\Modules\Core\User\Repositories;

use App\Modules\Core\Shared\VOs\UserEmail;
use App\Modules\Core\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\Core\User\Models\User;

class UserRepository implements UserRepositoryInterface
{
    //

    /**
     * Consulta un usuario por su dirección de correo electrónico.
     *
     * @param  UserEmail  $mail  Objeto de valor de correo electrónico.
     * @return User|null El usuario encontrado, o null si no existe.
     */
    public function consultarPorMail(UserEmail $mail): ?User
    {
        return User::where('email', '=', $mail->value())->first();
    }
}
