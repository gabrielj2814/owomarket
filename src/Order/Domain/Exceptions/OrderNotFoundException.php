<?php

declare(strict_types=1);

namespace Src\Order\Domain\Exceptions;

use Exception;

final class OrderNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("La orden con ID '{$id}' no fue encontrada.");
    }

    public static function withOrderNumber(string $orderNumber): self
    {
        return new self("La orden con número '{$orderNumber}' no fue encontrada.");
    }
}
