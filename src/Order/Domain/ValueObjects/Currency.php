<?php

declare(strict_types=1);

namespace Src\Order\Domain\ValueObjects;

use InvalidArgumentException;

final class Currency
{
    private string $code;

    public function __construct(?string $code = 'USD')
    {
        $normalized = strtoupper(trim($code ?? 'USD'));
        if (strlen($normalized) !== 3) {
            throw new InvalidArgumentException("El código de moneda debe tener exactamente 3 caracteres: '{$code}'.");
        }

        $this->code = $normalized;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code();
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
