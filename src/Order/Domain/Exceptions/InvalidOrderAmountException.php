<?php

declare(strict_types=1);

namespace Src\Order\Domain\Exceptions;

use Exception;

final class InvalidOrderAmountException extends Exception
{
    public static function negativeTotal(float $total): self
    {
        return new self("El total de la orden no puede ser negativo: {$total}.");
    }

    public static function mismatch(float $calculated, float $provided): self
    {
        return new self("El total provisto ({$provided}) no coincide con el cálculo de ítems, impuestos, envíos y descuentos ({$calculated}).");
    }
}
