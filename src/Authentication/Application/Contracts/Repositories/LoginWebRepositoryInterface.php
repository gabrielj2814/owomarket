<?php

namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\Uuid;

interface LoginWebRepositoryInterface
{
    /**
     * Método loginWebUser.
     */
    public function loginWebUser(UserEmail $email): void;

    /**
     * Método logoutWebUser.
     */
    public function logoutWebUser(Uuid $uuid): bool;
}
