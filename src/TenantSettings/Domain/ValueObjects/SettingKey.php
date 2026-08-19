<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\ValueObjects;

use Src\TenantSettings\Domain\Exceptions\InvalidSettingKeyException;

final class SettingKey
{
    private string $value;

    public function __construct(string $value)
    {
        $sanitized = strtolower(trim($value));
        if ($sanitized === '' || ! preg_match('/^[a-z0-9_\-\.]+$/', $sanitized)) {
            throw InvalidSettingKeyException::forValue($value);
        }

        $this->value = $sanitized;
    }

    public static function fromString(string $key): self
    {
        return new self($key);
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
