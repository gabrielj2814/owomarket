<?php

namespace Src\Tenant\Domain\ValuesObjects;

use Src\Shared\ValuesObjects\BoolValueObject;

final class DomainPrimary extends BoolValueObject
{

    public static function make(bool $value):self{
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value ? 'primary' : 'not primary';
    }

    public static function primary(): self
    {
        return new self(true);
    }

    public static function notPrimary(): self
    {
        return new self(false);
    }

    public function isPrimary(): bool
    {
        return $this->value;
    }


}
