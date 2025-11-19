<?php

namespace Src\Authentication\Domain\ValueObjects;

use Illuminate\Support\Str;
use Src\User\Domain\Exceptions\InvalidUuidException;

final class Uuid {

    private string $value;

    private function __construct(string $value) {
        if (!self::isValid($value)) {
            throw new InvalidUuidException(
                message: null,
                uuid: $value
            );
        }
        $this->value = $value;
    }

    public function value(): string {
        return $this->value;
    }

    public static function make(string $value):self{
        return new self($value);
    }

    public static function generate(): self {
        return new self(Str::uuid()->toString());
    }

    public static function isValid(string $uuid): bool {
        return Str::isUuid($uuid);
    }

    public function equals(self $other): bool {
        return $this->value === $other->value;
    }

    public function __toString(): string {
        return $this->value;
    }

    // Métodos adicionales útiles
    public function getVersion(): ?int {
        if (!self::isValid($this->value)) {
            return null;
        }

        $parts = explode('-', $this->value);
        return isset($parts[3]) ? (int) substr($parts[3], 0, 1) : null;
    }

    public function isV4(): bool {
        return $this->getVersion() === 4;
    }



}




?>
