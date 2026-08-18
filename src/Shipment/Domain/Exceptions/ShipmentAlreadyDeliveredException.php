<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\Exceptions;

use DomainException;

final class ShipmentAlreadyDeliveredException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("El envío '{$id}' ya ha sido entregado y no puede ser modificado.");
    }
}
