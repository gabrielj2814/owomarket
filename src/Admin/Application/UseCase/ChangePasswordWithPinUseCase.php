<?php

namespace Src\Admin\Application\UseCase;

use InvalidArgumentException;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\ValueObjects\Password;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;

class ChangePasswordWithPinUseCase
{
    private AdminRepositoryInterface $repository;
    private PasswordValidator $validator;
    private PasswordHasher $hasher;

    public function __construct(
        AdminRepositoryInterface $repository,
        PasswordValidator $validator,
        PasswordHasher $hasher
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->hasher = $hasher;
    }

    public function execute(string $uuid, string $pinInput, string $newPassword, string $newPasswordConfirmation): void
    {
        if ($newPassword !== $newPasswordConfirmation) {
            throw new InvalidArgumentException("La confirmación de la contraseña no coincide");
        }

        $adminUuid = Uuid::make($uuid);
        $admin = $this->repository->consultByUuid($adminUuid);

        if (!$admin) {
            throw new InvalidArgumentException("Administrador no encontrado");
        }

        if (!$admin->verifySecurityPin($pinInput)) {
            throw new InvalidArgumentException("El PIN de seguridad es incorrecto o ha expirado");
        }

        $passwordVO = Password::fromPlainText(
            $newPassword,
            $this->validator,
            $this->hasher
        );

        $admin->changePassword($passwordVO);
        $admin->clearSecurityPin();

        $savedAdmin = $this->repository->saveProfile($admin);

        if (!$savedAdmin) {
            throw new InvalidArgumentException("Error al actualizar la contraseña");
        }
    }
}
