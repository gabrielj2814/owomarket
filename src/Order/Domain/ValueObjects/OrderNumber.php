<?php

declare(strict_types=1);

namespace Src\Order\Domain\ValueObjects;

use InvalidArgumentException;

final class OrderNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidArgumentException('El número de orden no puede estar vacío.');
        }

        $this->value = $trimmed;
    }

    public static function generate(?string $prefix = 'ORD'): self
    {
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        $prefixStr = ! empty($prefix) ? strtoupper(trim($prefix)) : 'ORD';

        return new self("{$prefixStr}-{$date}-{$random}");
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
