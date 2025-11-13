<?php

namespace App\User\Domain\ValueObjects;

use Src\Shared\ValuesObjects\BoolValueObject;

final class UserStatus extends BoolValueObject
{
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
