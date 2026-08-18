<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Admin\Domain\ValueObjects\Uuid;

class ChangeStatuAdminByUuidUseCase
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
    public function execute(Uuid $uuid, UserStatus $statu): void
    {
        $this->admin_repository->changeStatu($uuid, $statu);
    }
}
