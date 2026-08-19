<?php

namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;
use Src\Shared\Domain\ValueObjects\Uuid;

class LogoutWebUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected LoginWebRepositoryInterface $loginWebRepository
    ) {}

    /**
     * Método execute.
     */
    public function execute(Uuid $uuid): bool
    {
        return $this->loginWebRepository->logoutWebUser($uuid);
    }
}
