<?php

namespace Src\Shared\Domain\ValueObjects;

use InvalidArgumentException;

final class UserName
{
    private string $value;

    private function __construct(string $name)
    {
        $this->value = $name;
    }

    public static function make(string $name): self
    {
        self::validate($name);

        return new self($name);
    }

    private static function validate(string $value): void
    {
        if ($value === '' || trim($value) === '') {
            throw new InvalidArgumentException('El nombre no puede estar vacio', 400);
        }

        if (strlen($value) <= 1) {
            throw new InvalidArgumentException('El nombre debe tener minumo 2 caracteres', 400);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
