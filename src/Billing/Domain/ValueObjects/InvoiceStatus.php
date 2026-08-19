<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use InvalidArgumentException;

final class InvoiceStatus
{
    public const DRAFT = 'draft';

    public const ISSUED = 'issued';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::ISSUED,
        self::PAID,
        self::CANCELLED,
        self::REFUNDED,
    ];

    private function __construct(
        private readonly string $value
    ) {
        if (! in_array($this->value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("El estado de factura '{$this->value}' no es válido.");
        }
    }

    public static function draft(): self
    {
        return new self(self::DRAFT);
    }

    public static function issued(): self
    {
        return new self(self::ISSUED);
    }

    public static function paid(): self
    {
        return new self(self::PAID);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function refunded(): self
    {
        return new self(self::REFUNDED);
    }

    public static function fromString(string $status): self
    {
        return new self(strtolower(trim($status)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->value === self::ISSUED;
    }

    public function isPaid(): bool
    {
        return $this->value === self::PAID;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->value === self::REFUNDED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->value, [self::DRAFT, self::ISSUED, self::PAID], true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
