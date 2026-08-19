<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\ValueObjects;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class SettingId
{
    private string $value;

    public function __construct(?string $value = null)
    {
        $val = $value ?? Uuid::uuid4()->toString();
        if (! Uuid::isValid($val)) {
            throw new InvalidArgumentException("El ID de configuración '{$val}' no es un UUID válido.");
        }
        $this->value = $val;
    }

    public static function random(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
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
