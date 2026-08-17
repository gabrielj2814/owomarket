<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\Exceptions;

use Exception;

final class ShippingRateNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('No se encontró la tarifa de envío "%s".', $identifier), 404);
    }
}
