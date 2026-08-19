<?php

namespace Src\Admin\Application\Contracts\Services;

use Illuminate\Http\UploadedFile;
use Src\Shared\Domain\ValueObjects\AvatarUrl;

interface AvatarStorageInterface
{
    /**
     * Subir imagen de perfil y retornar el ValueObject AvatarUrl.
     */
    public function uploadAvatar(UploadedFile $file, ?string $oldAvatarUrl = null): AvatarUrl;

    /**
     * Eliminar avatar antiguo de almacenamiento público.
     */
    public function deleteAvatar(string $avatarUrl): void;
}
