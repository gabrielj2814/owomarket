<?php


namespace Src\User\Application\Contracts\Repositories;

use Src\User\Domain\Entities\User;
use Src\User\Domain\ValueObjects\UserEmail;

interface UserRepositoryInterface
{
    //

    public function consultarPorMail(UserEmail $mail): ?User;


}



?>
