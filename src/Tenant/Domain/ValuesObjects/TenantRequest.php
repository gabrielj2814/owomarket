<?php

namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\BoolValueObject;
use Src\Shared\ValuesObjects\StringValueObject;

final class TenantRequest extends StringValueObject
{


    public const STATUS_APPROVED       = 'approved';
    public const STATUS_REJECTED       = 'rejected';
    public const STATUS_IN_PROGRESS    = 'in progress';

    private const ALLOWED_REQUEST = [
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_IN_PROGRESS,
    ];

    public static function make(bool $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (!in_array($value, self::ALLOWED_REQUEST)) {
            throw new InvalidArgumentException("Tipo de solicitud no válido: {$value}");
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function isApproved(): bool {
        return $this->value === self::STATUS_APPROVED;
    }

    public function isRejected(): bool {
        return $this->value === self::STATUS_REJECTED;
    }

    public function isInProgress(): bool {
        return $this->value === self::STATUS_IN_PROGRESS;
    }

    public static function approved(): self{
        return new self(self::STATUS_APPROVED);
    }

    public static function rejected(): self{
        return new self(self::STATUS_REJECTED);
    }

    public static function inProgress(): self{
        return new self(self::STATUS_IN_PROGRESS);
    }
}
