<?php

namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Entities\User;
use Src\Shared\Domain\ValueObjects\UserEmail;

class ConsultDataUserByEmailCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Método execute.
     */
    public function execute(UserEmail $mail): ?User
    {

        $user = $this->userRepository->consultarPorMail($mail);

        return $user;
    }
}
