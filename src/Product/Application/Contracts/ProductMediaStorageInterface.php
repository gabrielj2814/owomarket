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
    /**
     * Borra una imagen de producto.
     *
     * Hallazgo PR1: `$tenantId` no es opcional por comodidad — es lo que delimita QUE se
     * puede borrar. Sin el, cualquiera con permiso de catalogo borraba cualquier fichero
     * del disco publico pasando su ruta, incluidos avatares de administrador, PDFs de
     * factura y adjuntos de tickets.
     */
    public function deleteImage(string $imagePathOrUrl, ?string $tenantId = null): void;
}
