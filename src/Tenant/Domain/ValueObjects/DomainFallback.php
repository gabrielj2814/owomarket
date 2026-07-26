<?php

namespace Src\Tenant\Domain\ValueObjects;

use Src\Shared\Domain\ValueObjects\BoolValueObject;

final class DomainFallback extends BoolValueObject
{

    public static function make(bool $value):self{
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value ? 'fallback' : 'not fallback';
    }

    public static function fallback(): self
    {
        return new self(true);
    }

    public static function notFallback(): self
    {
        return new self(false);
    }

    public function isFallback(): bool
    {
        return $this->value;
    }


}
