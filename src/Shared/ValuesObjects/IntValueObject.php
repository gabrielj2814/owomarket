<?php

namespace Src\Shared\ValuesObjects;

abstract class IntValueObject
{
    protected int $value;

   protected function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    abstract protected function validate(int $value): void;

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->value === $other->value;
    }
}
