<?php

namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;

class LogoutApiUserUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(protected PersonalAccessTokenRepositoryInterface $personalAccessTokenRepository) {}

    /**
     * Método execute.
     */
    public function execute(string $tokenId): void
    {

        $this->personalAccessTokenRepository->deleteToken($tokenId);

    }
}
