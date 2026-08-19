<?php

namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\AuthUserRepositoryInterface;
use Src\Authentication\Domain\Entities\AuthUser;
use Src\Shared\Domain\ValueObjects\Uuid;

class EliminarAuthUserByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected AuthUserRepositoryInterface $auth_user_repository
    ) {}

    /**
     * Método execute.
     */
    public function execute(Uuid $uuid): ?AuthUser
    {
        return $this->auth_user_repository->delete($uuid);
    }
}
