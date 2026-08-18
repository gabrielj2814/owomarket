<?php

declare(strict_types=1);

namespace Src\Review\Domain\Exceptions;

use DomainException;

final class InvalidRatingException extends DomainException
{
    public static function forValue(int $value): self
    {
        return new self("La calificación '{$value}' es inválida. Debe ser un número entero entre 1 y 5 estrellas.");
    }
}
