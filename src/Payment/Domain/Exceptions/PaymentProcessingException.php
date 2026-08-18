<?php

declare(strict_types=1);

namespace Src\Payment\Domain\Exceptions;

use RuntimeException;

final class PaymentProcessingException extends RuntimeException
{
    public static function withReason(string $reason): self
    {
        return new self("Error al procesar el pago: {$reason}");
    }
}
