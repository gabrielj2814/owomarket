<?php


namespace Src\Admin\Infrastructure\Security;

use InvalidArgumentException;
use Src\Admin\Domain\Shared\Security\PasswordValidator;

class StrictPasswordValidator implements PasswordValidator
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 72;

    public function validate(string $password): void
    {
        if (strlen($password) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                "La contraseña debe tener al menos " . self::MIN_LENGTH . " caracteres"
            );
        }

        if (strlen($password) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "La contraseña no puede tener más de " . self::MAX_LENGTH . " caracteres"
            );
        }

        $rules = [
            'mayúscula' => '/[A-Z]/',
            'minúscula' => '/[a-z]/',
            'número' => '/[0-9]/',
            'carácter especial' => '/[!@#$%^&*()\-_=+{};:,<.>]/'
        ];

        foreach ($rules as $tipo => $patron) {
            if (!preg_match($patron, $password)) {
                throw new InvalidArgumentException(
                    "La contraseña debe contener al menos un $tipo"
                );
            }
        }
    }
}

?>
