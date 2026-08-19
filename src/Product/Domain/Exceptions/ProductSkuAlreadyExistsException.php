<?php

declare(strict_types=1);

namespace Src\Product\Domain\Exceptions;

use Exception;

final class ProductSkuAlreadyExistsException extends Exception
{
    public function __construct(string $sku)
    {
        parent::__construct(sprintf('Ya existe un producto con el SKU "%s".', $sku), 400);
    }
}
