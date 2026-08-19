<?php

namespace Src\Admin\Application\UseCase;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Entities\Admin;
use Src\Shared\Domain\ValueObjects\Uuid;

class ConsultAdminByUuidUseCase
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
    public function execute(Uuid $uuid): ?Admin
    {
        return $this->admin_repository->consultByUuid($uuid);
    }
}
