<?php

declare(strict_types=1);

namespace Src\Brand\Domain\Exceptions;

use Exception;

final class BrandNotFoundException extends Exception
{
    public function __construct(string|int $identifier)
    {
        parent::__construct(sprintf('No se encontró la marca con el identificador "%s".', (string) $identifier), 404);
    }
}
