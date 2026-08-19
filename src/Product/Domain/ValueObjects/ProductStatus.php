<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

final class ProductStatus
{
    public function __construct(
        private readonly bool $isVisible = true,
        private readonly bool $isFeatured = false,
        private readonly bool $isDigital = false
    ) {}

    public static function create(
        bool $isVisible = true,
        bool $isFeatured = false,
        bool $isDigital = false
    ): self {
        return new self($isVisible, $isFeatured, $isDigital);
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function isDigital(): bool
    {
        return $this->isDigital;
    }

    public function withVisibility(bool $isVisible): self
    {
        return new self($isVisible, $this->isFeatured, $this->isDigital);
    }

    public function withFeatured(bool $isFeatured): self
    {
        return new self($this->isVisible, $isFeatured, $this->isDigital);
    }
}
