<?php

namespace Src\Authentication\Infrastructure\Services;

use Src\Authentication\Application\Contracts\AuthServices as ContractsAuthServices;
use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\UserEmail;

class AuthServices implements ContractsAuthServices
{
    /**
     * Constructor de la clase.
     */
    public function __construct(protected UserRepositoryInterface $userRepository) {}

    /**
     * Método consultUserByEmail.
     */
    public function consultUserByEmail(UserEmail $email): ?User
    {
        return $this->userRepository->consultarPorMail($email);
    }
}
