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
    public function deleteImage(string $imagePathOrUrl): void
    {
        if (empty($imagePathOrUrl)) {
            return;
        }

        $relativePath = $imagePathOrUrl;
        $path = parse_url($imagePathOrUrl, PHP_URL_PATH);
        if ($path) {
            $relativePath = str_replace('/storage/', '', $path);
        }

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
