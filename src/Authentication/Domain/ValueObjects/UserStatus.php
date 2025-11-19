<?php

namespace Src\Authentication\Domain\ValueObjects;

use Src\Shared\ValuesObjects\BoolValueObject;

final class UserStatus extends BoolValueObject
{

    public static function make(bool $value):self{
        return new self($value);
    }

    public function activate(): self
    {
        return new self(true);
    }

    public function deactivate(): self
    {
        return new self(false);
    }

    public function isActive(): bool
    {
        return $this->value;
    }

    public function canLogin(): bool
    {
        return $this->isActive();
    }

    public function toString(): string
    {
        return $this->value ? 'active' : 'inactive';
    }

    public static function active(): self
    {
        return new self(true);
    }

    public static function inactive(): self
    {
        return new self(false);
    }
}
