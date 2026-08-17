<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

final class ProductDescription
{
    public function __construct(
        private readonly ?string $description = null,
        private readonly ?string $shortDescription = null
    ) {}

    public static function create(?string $description = null, ?string $shortDescription = null): self
    {
        return new self(
            description: $description !== null && trim($description) !== '' ? trim($description) : null,
            shortDescription: $shortDescription !== null && trim($shortDescription) !== '' ? trim($shortDescription) : null
        );
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function shortDescription(): ?string
    {
        return $this->shortDescription;
    }
}
