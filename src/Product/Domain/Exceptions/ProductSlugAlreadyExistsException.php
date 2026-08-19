<?php

declare(strict_types=1);

namespace Src\Product\Domain\Exceptions;

use Exception;

final class ProductSlugAlreadyExistsException extends Exception
{
    public function __construct(string $slug)
    {
        parent::__construct(sprintf('Ya existe un producto con el slug "%s".', $slug), 400);
    }
}
