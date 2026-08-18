<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\ValueObjects;

use InvalidArgumentException;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';

    public static function fromString(string $status): self
    {
        $statusLower = strtolower(trim($status));
        foreach (self::cases() as $case) {
            if ($case->value === $statusLower) {
                return $case;
            }
        }

        throw new InvalidArgumentException("El estado de envío '{$status}' no es válido. Estados permitidos: pending, in_transit, delivered.");
    }
}
