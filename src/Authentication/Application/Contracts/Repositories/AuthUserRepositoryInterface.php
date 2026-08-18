<?php

namespace Src\Authentication\Application\Contracts\Repositories;

use Src\Authentication\Domain\Entities\AuthUser;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\Uuid;

interface AuthUserRepositoryInterface
{
    /**
     * Método create.
     */
    public function create(User $user): ?AuthUser;

    /**
     * Método consult.
     */
    public function consult(Uuid $uuid): ?AuthUser;

    /**
     * Método delete.
     */
    public function delete(Uuid $uuid): void;
}
