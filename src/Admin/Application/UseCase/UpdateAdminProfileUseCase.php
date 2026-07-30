<?php

namespace Src\Admin\Application\UseCase;

use InvalidArgumentException;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\PhoneNumber;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Admin\Domain\ValueObjects\Uuid;

class UpdateAdminProfileUseCase
{
    private AdminRepositoryInterface $repository;

    public function __construct(AdminRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function execute(string $uuid, string $name, ?string $phone = null): Admin
    {
        $adminUuid = Uuid::make($uuid);
        $admin = $this->repository->consultByUuid($adminUuid);

        if (!$admin) {
            throw new InvalidArgumentException("Administrador no encontrado");
        }

        $userName = UserName::make($name);
        $userPhone = ($phone !== null && trim($phone) !== '') ? PhoneNumber::make($phone) : null;

        $admin->updateProfile($userName, $userPhone);

        $updatedAdmin = $this->repository->saveProfile($admin);

        if (!$updatedAdmin) {
            throw new InvalidArgumentException("No se pudo actualizar el perfil del administrador");
        }

        return $updatedAdmin;
    }
}
