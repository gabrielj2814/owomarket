<?php

declare(strict_types=1);

namespace Src\Product\Application\Contracts;

use Illuminate\Http\UploadedFile;

interface ProductMediaStorageInterface
{
    /**
     * Sube una imagen al disco de almacenamiento del tenant.
     *
     * @return array{url: string, path: string, filename: string}
     */
    public function uploadImage(UploadedFile $file, ?string $tenantId = null): array;

    /**
     * Elimina físicamente una imagen del disco de almacenamiento.
     */
    public function deleteImage(string $imagePathOrUrl): void;
}
