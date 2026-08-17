<?php

declare(strict_types=1);

namespace Src\Product\Domain\Exceptions;

use Exception;

final class ProductNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('No se encontró el producto "%s".', $identifier), 404);
    }
}
