<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use InvalidArgumentException;

final class CustomerPhone
{
    private function __construct(
        private readonly string $value
    ) {
        $trimmed = trim($this->value);
        if (mb_strlen($trimmed) > 50) {
            throw new InvalidArgumentException('El teléfono no puede exceder 50 caracteres.');
        }
    }

    public static function fromString(string $phone): self
    {
        return new self(trim($phone));
    }

    public static function nullable(?string $phone): ?self
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        return new self(trim($phone));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
