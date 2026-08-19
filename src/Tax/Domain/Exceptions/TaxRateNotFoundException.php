<?php

declare(strict_types=1);

namespace Src\Tax\Domain\Exceptions;

use Exception;

final class TaxRateNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('No se encontró la tasa de impuesto "%s".', $identifier), 404);
    }
}
