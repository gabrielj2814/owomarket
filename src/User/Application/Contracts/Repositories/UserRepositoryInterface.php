<?php

namespace Src\User\Application\Contracts\Repositories;

use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\User\Domain\Entities\User;

interface UserRepositoryInterface
{
    //

    /**
     * Método consultarPorMail.
     */
    public function consultarPorMail(UserEmail $mail): ?User;
}
