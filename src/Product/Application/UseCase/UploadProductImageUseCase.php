<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Illuminate\Http\UploadedFile;
use Src\Product\Application\Contracts\ProductMediaStorageInterface;

final class UploadProductImageUseCase
{
    public function __construct(
        private readonly ProductMediaStorageInterface $mediaStorage
    ) {}

    /**
     * @return array{url: string, path: string, filename: string}
     */
    public function execute(UploadedFile $file, ?string $tenantId = null): array
    {
        return $this->mediaStorage->uploadImage($file, $tenantId);
    }
}
