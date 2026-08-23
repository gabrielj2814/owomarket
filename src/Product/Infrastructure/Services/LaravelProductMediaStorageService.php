<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Product\Application\Contracts\ProductMediaStorageInterface;

final class LaravelProductMediaStorageService implements ProductMediaStorageInterface
{
    /**
     * Sube una imagen al disco público del tenant.
     *
     * @return array{url: string, path: string, filename: string}
     */
    public function uploadImage(UploadedFile $file, ?string $tenantId = null): array
    {
        $extension = $file->getClientOriginalExtension();
        $filename = time().'_'.Str::random(12).'.'.$extension;

        $directory = $tenantId ? "tenants/{$tenantId}/products" : 'products';
        $path = $file->storeAs($directory, $filename, 'public');

        $url = Storage::disk('public')->url($path);

        return [
            'url' => $url,
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * Elimina físicamente una imagen del disco público.
     */
    public function deleteImage(string $imagePathOrUrl, ?string $tenantId = null): void
    {
        if (empty($imagePathOrUrl)) {
            return;
        }

        $relativePath = $imagePathOrUrl;
        $path = parse_url($imagePathOrUrl, PHP_URL_PATH);
        if ($path) {
            $relativePath = str_replace('/storage/', '', $path);
        }

        $relativePath = ltrim($relativePath, '/');

        if (! $this->perteneceAlInquilino($relativePath, $tenantId)) {
            return;
        }

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    /**
     * Hallazgo PR1: la unica frontera que impide borrar ficheros ajenos.
     *
     * Antes aqui no habia nada: la ruta llegaba del request y se borraba tal cual. El disco
     * `public` lo comparten las imagenes de TODAS las tiendas, los avatares de
     * administrador, los PDFs de factura y los adjuntos de soporte, asi que bastaba con
     * sesion de tienda y `manage_catalog` para borrar cualquiera de ellos.
     *
     * No se comprueba contra `product_images` —que seria lo natural— porque el formulario
     * borra una imagen recien subida ANTES de guardar el producto: en ese momento no existe
     * ninguna fila que la reclame, y exigirla romperia el caso legitimo.
     *
     * Asi que la frontera es la misma que usa `uploadImage()` al escribir:
     * `tenants/{id}/products/`. Simetria a proposito — si un dia cambia el esquema de
     * rutas, las dos mitades estan a la vista en el mismo fichero.
     */
    private function perteneceAlInquilino(string $relativePath, ?string $tenantId): bool
    {
        // Nada de subir por el arbol. Flysystem ya lo impide, pero un `..` en la ruta
        // significa que quien la manda esta intentando algo, y no hay motivo legitimo.
        if (str_contains($relativePath, '..')) {
            return false;
        }

        if ($tenantId === null || $tenantId === '') {
            // Fuera de una tienda no hay a quien atribuir el fichero. Se rechaza en vez de
            // dejarlo pasar: negar de mas es recuperable, borrar de mas no.
            return false;
        }

        return str_starts_with($relativePath, "tenants/{$tenantId}/products/");
    }
}
