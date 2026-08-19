<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Exceptions;

use DomainException;

final class InvoiceNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("La factura con ID '{$id}' no fue encontrada.");
    }

    public static function withNumber(string $number): self
    {
        return new self("La factura con número '{$number}' no fue encontrada.");
    }
}
