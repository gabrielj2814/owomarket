<?php

declare(strict_types=1);

namespace Src\Shipment\Domain\Exceptions;

use DomainException;

final class InvalidShipmentDataException extends DomainException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
