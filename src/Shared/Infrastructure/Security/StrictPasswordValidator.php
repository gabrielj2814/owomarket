<?php

namespace Src\Shared\Infrastructure\Security;

use InvalidArgumentException;
use Src\Shared\Domain\Contracts\PasswordValidator;

class StrictPasswordValidator implements PasswordValidator
{
    private const MIN_LENGTH = 8;

    private const MAX_LENGTH = 72;

    public function validate(string $plainPassword): void
    {
        if (strlen($plainPassword) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('La contraseña debe tener al menos %d caracteres', self::MIN_LENGTH)
            );
        }

        if (strlen($plainPassword) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('La contraseña no puede tener más de %d caracteres', self::MAX_LENGTH)
            );
        }

        $rules = [
            'mayúscula' => '/[A-Z]/',
            'minúscula' => '/[a-z]/',
            'número' => '/[0-9]/',
            'carácter especial' => '/[!@#$%^&*()\-_=+{};:,<.>]/',
        ];

        foreach ($rules as $tipo => $patron) {
            if (! preg_match($patron, $plainPassword)) {
                throw new InvalidArgumentException(
                    "La contraseña debe contener al menos un $tipo"
                );
            }
        }
    }
}
