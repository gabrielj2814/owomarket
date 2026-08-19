<?php

declare(strict_types=1);

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class ProductSku extends StringValueObject
{
    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        return new self($normalized);
    }

    protected function validate(string $value): void
    {
        $normalized = strtoupper(trim($value));

        if (mb_strlen($normalized) < 2 || mb_strlen($normalized) > 50) {
            throw new InvalidArgumentException('El SKU del producto debe tener entre 2 y 50 caracteres.');
        }

        if (! preg_match('/^[A-Z0-9\-_]+$/', $normalized)) {
            throw new InvalidArgumentException('El SKU solo puede contener caracteres alfanuméricos, guiones y guiones bajos.');
        }
    }
}
