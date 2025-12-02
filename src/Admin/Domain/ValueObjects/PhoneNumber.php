<?php

namespace Src\Admin\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class PhoneNumber extends StringValueObject
{

    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException("El número de teléfono no puede estar vacío");
        }

        // Limpiar y validar formato internacional
        $cleaned = preg_replace('/\D/', '', $value);

        if (strlen($cleaned) < 10) {
            throw new InvalidArgumentException("El número de teléfono debe tener al menos 10 dígitos");
        }

        if (strlen($cleaned) > 15) {
            throw new InvalidArgumentException("El número de teléfono no puede exceder 15 dígitos");
        }
    }

    public function getInternationalFormat(): string
    {
        $cleaned = preg_replace('/\D/', '', $this->value);

        // Asumir formato mexicano como ejemplo, ajusta según tu país
        if (strlen($cleaned) === 10) {
            return '+52' . $cleaned;
        }

        return '+' . $cleaned;
    }

    public function getNationalFormat(): string
    {
        $cleaned = preg_replace('/\D/', '', $this->value);

        if (strlen($cleaned) === 10) {
            return substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6, 4);
        }

        return $this->value;
    }

    public function normalizedEquals(self $other): bool
    {
        // Método adicional si necesitas comparación normalizada
        $thisCleaned = preg_replace('/\D/', '', $this->value);
        $otherCleaned = preg_replace('/\D/', '', $other->value);

        return $thisCleaned === $otherCleaned;
    }
}
