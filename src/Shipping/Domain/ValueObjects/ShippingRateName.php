<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\ValueObjects\StringValueObject;

final class ShippingRateName extends StringValueObject
{
    public static function make(string $value): self
    {
        return new self($value);
    }

    protected function validate(string $value): void
    {
        $trimmed = trim($value);

        if (mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 150) {
            throw new InvalidArgumentException('El nombre de la tarifa de envío debe tener entre 2 y 150 caracteres.');
        }
    }
}
