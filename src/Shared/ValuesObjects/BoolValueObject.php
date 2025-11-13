<?php

namespace Src\Shared\ValuesObjects;

abstract class BoolValueObject
{
    protected bool $value;

    private function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(bool $value): void
    {
        // Validación base para booleanos
        // Puede ser sobrescrita en clases hijas si se necesita validación adicional
    }

    public function value(): bool
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->value === $other->value;
    }
}
