<?php

declare(strict_types=1);

namespace Src\Product\Application\DTOs;

final class ProductImageData
{
    public function __construct(
        public readonly string $imagePath,
        public readonly ?string $altText = null,
        public readonly bool $isDefault = false,
        public readonly int $order = 0,
        public readonly ?string $id = null
    ) {}
}
