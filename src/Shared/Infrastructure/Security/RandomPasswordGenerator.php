<?php

namespace Src\Shared\Infrastructure\Security;

use InvalidArgumentException;
use Src\Shared\Domain\Contracts\PasswordGenerator;
use Src\Shared\Domain\Contracts\PasswordValidator;

class RandomPasswordGenerator implements PasswordGenerator
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';

    private const NUMBERS = '0123456789';

    private const SPECIAL_CHARS = '!@#$%^&*()-_=+{};:,<.>';

    public function __construct(
        protected PasswordValidator $validator
    ) {}

    public function generate(int $length = 12): string
    {
        if ($length < 8 || $length > 72) {
            throw new InvalidArgumentException('La longitud debe estar entre 8 y 72 caracteres.');
        }

        $allChars = self::UPPERCASE.self::LOWERCASE.self::NUMBERS.self::SPECIAL_CHARS;
        $allCharsLength = strlen($allChars);

        $password = '';
        $password .= self::UPPERCASE[random_int(0, strlen(self::UPPERCASE) - 1)];
        $password .= self::LOWERCASE[random_int(0, strlen(self::LOWERCASE) - 1)];
        $password .= self::NUMBERS[random_int(0, strlen(self::NUMBERS) - 1)];
        $password .= self::SPECIAL_CHARS[random_int(0, strlen(self::SPECIAL_CHARS) - 1)];

        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $allChars[random_int(0, $allCharsLength - 1)];
        }

        $password = str_shuffle($password);

        try {
            $this->validator->validate($password);
        } catch (InvalidArgumentException) {
            return $this->generate($length);
        }

        return $password;
    }
}
