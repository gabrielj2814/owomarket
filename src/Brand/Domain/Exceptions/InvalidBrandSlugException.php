<?php

declare(strict_types=1);

namespace Src\Brand\Domain\Exceptions;

use Exception;

final class InvalidBrandSlugException extends Exception
{
    public function __construct(string $slug)
    {
        parent::__construct(sprintf('El slug de marca "%s" no es válido.', $slug), 400);
    }
}
