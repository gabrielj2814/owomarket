<?php

namespace Src\Admin\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class PinVerification extends StringValueObject
{
    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (strlen($value) !== 6) {
            throw new InvalidArgumentException("El PIN debe tener exactamente 6 dígitos");
        }

        if (!preg_match('/^\d+$/', $value)) {
            throw new InvalidArgumentException("El PIN debe contener solo números");
        }

        // Evitar PINs demasiado simples
        if (preg_match('/(\d)\1{2,}/', $value)) { // 111111, 222222, etc.
            throw new InvalidArgumentException("El PIN no puede tener números repetidos consecutivos");
        }
    }

    public static function generate(): self
    {
        $pin = '';
        do {
            $pin = sprintf("%06d", random_int(0, 999999));
        } while (preg_match('/(\d)\1{2,}/', $pin)); // Regenerar si es muy simple

        return new self($pin);
    }

    public function isExpired(DateTimeImmutable $createdAt, int $expiryMinutes = 15): bool
    {
        $now = new DateTimeImmutable();
        $expiryTime = $createdAt->modify("+{$expiryMinutes} minutes");

        return $now > $expiryTime;
    }

    public function verify(string $inputPin): bool
    {
        return $this->value === $inputPin;
    }
}
