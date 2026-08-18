<?php

declare(strict_types=1);

namespace Src\Payment\Domain\ValueObjects;

use InvalidArgumentException;

final class PaymentStatus
{
    public const PENDING = 'pending';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public const CANCELLED = 'cancelled';

    private const VALID_STATUSES = [
        self::PENDING,
        self::COMPLETED,
        self::FAILED,
        self::REFUNDED,
        self::CANCELLED,
    ];

    private function __construct(
        private readonly string $value
    ) {
        if (! in_array($this->value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("El estado de pago '{$this->value}' no es válido.");
        }
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function failed(): self
    {
        return new self(self::FAILED);
    }

    public static function refunded(): self
    {
        return new self(self::REFUNDED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function fromString(string $status): self
    {
        return new self(strtolower(trim($status)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->value === self::REFUNDED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
