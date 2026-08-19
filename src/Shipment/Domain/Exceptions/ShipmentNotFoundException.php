<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\Exceptions;

use DomainException;

final class ShipmentNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("No se encontró el envío con identificador '{$id}'.");
    }

    public static function forOrderId(string $orderId): self
    {
        return new self("No se encontró ningún envío asociado a la orden '{$orderId}'.");
    }
}
