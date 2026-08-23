<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\ProductMediaStorageInterface;

final class DeleteProductImageUseCase
{
    public function __construct(
        private readonly ProductMediaStorageInterface $mediaStorage
    ) {}

    /**
     * Hallazgo PR1: el inquilino viaja hasta el almacenamiento, que es quien decide si esa
     * ruta le pertenece. Antes se borraba cualquier fichero del disco publico.
     */
    public function execute(string $imagePathOrUrl, ?string $tenantId = null): void
    {
        $this->mediaStorage->deleteImage($imagePathOrUrl, $tenantId);
    }
}
