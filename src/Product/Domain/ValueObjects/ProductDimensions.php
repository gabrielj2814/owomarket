<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;

final class ProductDimensions
{
    public function __construct(
        private readonly ?float $weight = null,
        private readonly ?float $height = null,
        private readonly ?float $width = null,
        private readonly ?float $length = null
    ) {}

    public static function create(
        ?float $weight = null,
        ?float $height = null,
        ?float $width = null,
        ?float $length = null
    ): self {
        if ($weight !== null && $weight < 0) {
            throw new InvalidArgumentException('El peso no puede ser negativo.');
        }
        if ($height !== null && $height < 0) {
            throw new InvalidArgumentException('La altura no puede ser negativa.');
        }
        if ($width !== null && $width < 0) {
            throw new InvalidArgumentException('El ancho no puede ser negativo.');
        }
        if ($length !== null && $length < 0) {
            throw new InvalidArgumentException('La longitud no puede ser negativa.');
        }

        return new self(
            weight: $weight !== null ? round($weight, 2) : null,
            height: $height !== null ? round($height, 2) : null,
            width: $width !== null ? round($width, 2) : null,
            length: $length !== null ? round($length, 2) : null
        );
    }

    public function weight(): ?float
    {
        return $this->weight;
    }

    public function height(): ?float
    {
        return $this->height;
    }

    public function width(): ?float
    {
        return $this->width;
    }

    public function length(): ?float
    {
        return $this->length;
    }
}
