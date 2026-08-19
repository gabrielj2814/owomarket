<?php

declare(strict_types=1);

namespace Src\Attribute\Domain\Exceptions;

use Exception;

final class InvalidAttributeSlugException extends Exception
{
    public function __construct(string $slug)
    {
        parent::__construct(sprintf('El slug del atributo "%s" no es válido.', $slug), 400);
    }
}
