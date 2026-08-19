<?php

namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\Entities\User;
use Src\Shared\Domain\ValueObjects\UserEmail;

interface UserRepositoryInterface
{
    //

    /**
     * Método consultarPorMail.
     */
    public function consultarPorMail(UserEmail $mail): ?User;
}
