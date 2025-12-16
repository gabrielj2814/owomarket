<?php
declare(strict_types=1);

namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\BoolValueObject;
use Src\Shared\ValuesObjects\StringValueObject;

final class TenantStatus extends StringValueObject
{
    public const STATUS_ACTIVE       = 'active';
    public const STATUS_INACTIVE     = 'inactive';
    public const STATUS_SUSPENDED    = 'suspended';

    private const ALLOWED_STATUS = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
    ];

    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (!in_array($value, self::ALLOWED_STATUS)) {
            throw new InvalidArgumentException("Tipo de estado no es válido: {$value}");
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isActive(): bool {
        return $this->value === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool {
        return $this->value === self::STATUS_INACTIVE;
    }

    public function isSuspended(): bool {
        return $this->value === self::STATUS_SUSPENDED;
    }

    public static function active(): self{
        return new self(self::STATUS_ACTIVE);
    }

    public static function inactive(): self{
        return new self(self::STATUS_INACTIVE);
    }

    public static function suspended(): self{
        return new self(self::STATUS_SUSPENDED);
    }
}
