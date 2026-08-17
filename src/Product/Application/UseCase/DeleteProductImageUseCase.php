<?php

declare(strict_types=1);

namespace Src\Product\Application\UseCase;

use Src\Product\Application\Contracts\ProductMediaStorageInterface;

final class DeleteProductImageUseCase
{
    public function __construct(
        private readonly ProductMediaStorageInterface $mediaStorage
    ) {}

    public function execute(string $imagePathOrUrl): void
    {
        $this->mediaStorage->deleteImage($imagePathOrUrl);
    }
}
