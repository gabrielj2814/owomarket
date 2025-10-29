<?php


namespace App\Modules\Core\User\Contracts\Repositories;

use App\Modules\Core\Shared\VOs\UserEmail;
use App\Modules\Core\User\Models\User;

interface UserRepositoryInterface
{
    //

    public function consultarPorMail(UserEmail $mail): ?User;


}



?>
