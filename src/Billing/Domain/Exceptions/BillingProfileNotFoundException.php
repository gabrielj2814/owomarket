<?php

declare(strict_types=1);

namespace Src\Billing\Domain\Exceptions;

use DomainException;

final class BillingProfileNotFoundException extends DomainException
{
    public static function defaultProfile(): self
    {
        return new self('No se ha configurado el perfil de facturación de la tienda.');
    }
}
