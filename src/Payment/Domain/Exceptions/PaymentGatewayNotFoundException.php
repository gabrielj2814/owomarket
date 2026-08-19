<?php

declare(strict_types=1);

namespace Src\Payment\Domain\Exceptions;

use DomainException;

final class PaymentGatewayNotFoundException extends DomainException
{
    public static function withIdentifier(string $identifier): self
    {
        return new self("La pasarela de pago '{$identifier}' no está registrada o no está soportada.");
    }
}
