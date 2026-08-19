<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Exceptions;

use DomainException;

final class InvalidInvoiceStateException extends DomainException
{
    public static function cannotBeCancelled(string $currentStatus): self
    {
        return new self("No se puede anular una factura con estado '{$currentStatus}'.");
    }
}
