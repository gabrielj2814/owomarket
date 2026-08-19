<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Shared\Domain\ValueObjects\Uuid;

class DeleteAdminByUuidUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected AdminRepositoryInterface $admin_repository_interface
    ) {}

    /**
     * Método execute.
     */
    public function execute(Uuid $uuid): void
    {
        $this->admin_repository_interface->eliminar($uuid);
    }
}
