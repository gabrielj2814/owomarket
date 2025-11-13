<?php

namespace Src\Shared\ValuesObjects;

abstract class StringValueObject
{
    protected string $value;

    protected function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    abstract protected function validate(string $value): void;

    public function value(): string
    {
        return $this->value;
    }

    // Método genérico que funciona para cualquier StringValueObject
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
