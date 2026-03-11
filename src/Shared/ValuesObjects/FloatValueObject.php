<?php

namespace Src\Shared\ValuesObjects;

abstract class FloatValueObject
{
    protected float $value;

    protected function __construct(float $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    abstract protected function validate(float $value): void;

    public function value(): float
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->value === $other->value;
    }
}


?>
