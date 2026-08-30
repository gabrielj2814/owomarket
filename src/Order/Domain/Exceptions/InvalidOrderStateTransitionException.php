<?php

declare(strict_types=1);

namespace Src\Order\Domain\Exceptions;

use Exception;

final class InvalidOrderStateTransitionException extends Exception
{
    public static function from(string $currentStatus, string $targetStatus): self
    {
        return new self("No se puede cambiar el estado de la orden de '{$currentStatus}' a '{$targetStatus}'.");
    }

    /** Hallazgo OR1: mismas transiciones invalidas, pero sobre el estado del pago. */
    public static function payment(string $currentStatus, string $targetStatus): self
    {
        return new self("No se puede cambiar el estado del pago de '{$currentStatus}' a '{$targetStatus}'.");
    }
}
