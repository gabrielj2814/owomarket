<?php

declare(strict_types=1);

namespace Src\Review\Domain\Exceptions;

use DomainException;

final class ReviewNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("No se encontró la reseña con identificador '{$id}'.");
    }
}
