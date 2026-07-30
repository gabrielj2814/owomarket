<?php

namespace Src\Admin\Application\UseCase;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Application\Contracts\Services\AvatarStorageInterface;
use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\Uuid;

class UploadAdminAvatarUseCase
{
    private AdminRepositoryInterface $repository;
    private AvatarStorageInterface $storageService;

    public function __construct(
        AdminRepositoryInterface $repository,
        AvatarStorageInterface $storageService
    ) {
        $this->repository = $repository;
        $this->storageService = $storageService;
    }

    public function execute(string $uuid, UploadedFile $file): Admin
    {
        $adminUuid = Uuid::make($uuid);
        $admin = $this->repository->consultByUuid($adminUuid);

        if (!$admin) {
            throw new InvalidArgumentException("Administrador no encontrado");
        }

        $oldAvatarUrl = $admin->getAvatar()?->value();

        $newAvatarUrl = $this->storageService->uploadAvatar($file, $oldAvatarUrl);

        $admin->updateAvatar($newAvatarUrl);

        $updatedAdmin = $this->repository->saveProfile($admin);

        if (!$updatedAdmin) {
            throw new InvalidArgumentException("Error al guardar la foto de perfil en la base de datos");
        }

        return $updatedAdmin;
    }
}
