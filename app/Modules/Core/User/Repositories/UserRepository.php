<?php



namespace App\Modules\Core\User\Repositories;

use App\Modules\Core\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\Core\User\Models\User;
use App\Modules\Core\Shared\VOs\UserEmail;

class UserRepository implements UserRepositoryInterface
{
    //


    public function consultarPorMail(UserEmail $mail): ?User
    {
        return User::where("email","=",$mail->value())->first();
    }




}



?>
