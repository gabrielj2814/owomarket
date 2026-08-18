<?php

declare(strict_types=1);

namespace Src\Customer\Domain\ValueObjects;

use InvalidArgumentException;

final class Gender
{
    public const MALE = 'male';

    public const FEMALE = 'female';

    public const OTHER = 'other';

    private const VALID_GENDERS = [
        self::MALE,
        self::FEMALE,
        self::OTHER,
    ];

    private function __construct(
        private readonly string $value
    ) {
        if (! in_array($this->value, self::VALID_GENDERS, true)) {
            throw new InvalidArgumentException("El género '{$this->value}' no es válido.");
        }
    }

    public static function fromString(string $gender): self
    {
        return new self(strtolower(trim($gender)));
    }

    public static function nullable(?string $gender): ?self
    {
        if ($gender === null || trim($gender) === '') {
            return null;
        }

        return new self(strtolower(trim($gender)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}
