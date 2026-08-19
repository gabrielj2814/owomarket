<?php

declare(strict_types=1);

use Src\Shared\Infrastructure\Security\RandomPasswordGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;

test('RandomPasswordGenerator genera contraseñas válidas que cumplen las reglas de StrictPasswordValidator', function () {
    $validator = new StrictPasswordValidator;
    $generator = new RandomPasswordGenerator($validator);

    $lengths = [8, 12, 16, 24, 32, 72];

    foreach ($lengths as $length) {
        for ($i = 0; $i < 10; $i++) {
            $password = $generator->generate($length);

            expect(strlen($password))->toBe($length);
            expect(fn () => $validator->validate($password))->not->toThrow(InvalidArgumentException::class);
        }
    }
});

test('RandomPasswordGenerator rechaza longitudes fuera del rango 8 a 72', function () {
    $validator = new StrictPasswordValidator;
    $generator = new RandomPasswordGenerator($validator);

    expect(fn () => $generator->generate(7))->toThrow(InvalidArgumentException::class);
    expect(fn () => $generator->generate(73))->toThrow(InvalidArgumentException::class);
});
