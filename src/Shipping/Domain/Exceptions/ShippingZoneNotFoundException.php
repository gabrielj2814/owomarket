<?php

declare(strict_types=1);

namespace Src\Shipping\Domain\Exceptions;

use Exception;

final class ShippingZoneNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('No se encontró la zona de envío "%s".', $identifier), 404);
    }
}
