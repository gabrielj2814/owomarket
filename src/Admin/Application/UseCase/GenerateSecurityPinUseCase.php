<?php

namespace Src\Admin\Application\UseCase;

use InvalidArgumentException;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Application\Contracts\Services\SecurityPinMailerInterface;
use Src\Admin\Domain\ValueObjects\Uuid;

class GenerateSecurityPinUseCase
{
    private AdminRepositoryInterface $repository;
    private SecurityPinMailerInterface $mailerService;

    public function __construct(
        AdminRepositoryInterface $repository,
        SecurityPinMailerInterface $mailerService
    ) {
        $this->repository = $repository;
        $this->mailerService = $mailerService;
    }

    public function execute(string $uuid): void
    {
        $adminUuid = Uuid::make($uuid);
        $admin = $this->repository->consultByUuid($adminUuid);

        if (!$admin) {
            throw new InvalidArgumentException("Administrador no encontrado");
        }

        $pin = $admin->generateSecurityPin(15);

        $savedAdmin = $this->repository->saveProfile($admin);

        if (!$savedAdmin) {
            throw new InvalidArgumentException("Error al registrar el PIN de seguridad");
        }

        $this->mailerService->sendPinMail(
            $admin->getEmail(),
            $admin->getName(),
            $pin
        );
    }
}
