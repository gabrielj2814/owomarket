<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\UserEmail;

class ConsultAdminByEmailUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected AdminRepositoryInterface $admin_repository
    ) {}

    /**
     * Método execute.
     */
    public function execute(UserEmail $email): ?Admin
    {
        return $this->admin_repository->consultByEmail($email);
    }
}
