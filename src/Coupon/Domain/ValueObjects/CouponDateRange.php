<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final class CouponDateRange
{
    private function __construct(
        private readonly string $validFrom,
        private readonly string $validTo
    ) {}

    public static function create(string $validFrom, string $validTo): self
    {
        $from = DateTimeImmutable::createFromFormat('Y-m-d', substr($validFrom, 0, 10));
        $to = DateTimeImmutable::createFromFormat('Y-m-d', substr($validTo, 0, 10));

        if (! $from || ! $to) {
            throw new InvalidArgumentException('El formato de las fechas debe ser Y-m-d.');
        }

        if ($from > $to) {
            throw new InvalidArgumentException('La fecha de inicio (valid_from) no puede ser posterior a la fecha de expiración (valid_to).');
        }

        return new self($from->format('Y-m-d'), $to->format('Y-m-d'));
    }

    public function validFrom(): string
    {
        return $this->validFrom;
    }

    public function validTo(): string
    {
        return $this->validTo;
    }

    public function isDateWithin(string $date): bool
    {
        $check = DateTimeImmutable::createFromFormat('Y-m-d', substr($date, 0, 10));
        $from = DateTimeImmutable::createFromFormat('Y-m-d', $this->validFrom);
        $to = DateTimeImmutable::createFromFormat('Y-m-d', $this->validTo);

        if (! $check || ! $from || ! $to) {
            return false;
        }

        return $check >= $from && $check <= $to;
    }
}
