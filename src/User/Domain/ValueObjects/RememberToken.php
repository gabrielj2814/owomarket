RememberToken<?php

namespace App\Authentication\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class RememberToken extends StringValueObject
{
    protected function validate(string $value): void
    {
        if (empty($value)) {
            return; // Permitir null/empty
        }

        if (strlen($value) !== 100) {
            throw new InvalidArgumentException("El remember token debe tener 100 caracteres");
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            throw new InvalidArgumentException("El remember token contiene caracteres inválidos");
        }
    }

    public static function generate(): self
    {
        $length = 100;
        $token = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return new self($token);
    }

    public function isValid(): bool
    {
        return !empty($this->value);
    }

    public static function empty(): self
    {
        return new self('');
    }
}
