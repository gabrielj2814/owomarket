<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\User\Infrastructure\Eloquent\Models\User;

/**
 * Pendiente P1 / hallazgo N22: la Fase 2.1 vetó `RootUserSeeder` fuera de local y testing,
 * y con eso una instalación nueva se quedaba **sin ningún camino** para crear el primer
 * superadministrador.
 */
test('crea el superadministrador pidiendo los datos por consola', function () {
    $email = 'root_'.bin2hex(random_bytes(4)).'@owomarket.com';

    $this->artisan('admin:create-super')
        ->expectsQuestion('Nombre del administrador', 'Gabriel')
        ->expectsQuestion('Correo electrónico', $email)
        ->expectsQuestion('Contraseña', 'Test_12345678')
        ->expectsQuestion('Confirma la contraseña', 'Test_12345678')
        ->assertSuccessful();

    $user = User::where('email', $email)->first();

    expect($user)->not->toBeNull()
        ->and($user->type)->toBe('super_admin')
        ->and($user->is_active)->toBeTrue()
        ->and($user->password)->not->toBe('Test_12345678');
});

// Resetear la contraseña de otra persona desde la consola era justo lo que hacía mal el
// seeder de desarrollo con su `updateOrCreate`.
test('se niega a sobrescribir un usuario existente', function () {
    $email = 'existente_'.bin2hex(random_bytes(4)).'@owomarket.com';

    User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ya Existe',
        'email' => $email,
        'type' => 'super_admin',
        'password' => bcrypt('OtraCosa_123'),
    ]);

    $this->artisan('admin:create-super')
        ->expectsQuestion('Nombre del administrador', 'Impostor')
        ->expectsQuestion('Correo electrónico', $email)
        ->assertFailed();

    expect(User::where('email', $email)->value('name'))->toBe('Ya Existe');
});

test('rechaza contraseñas que no coinciden', function () {
    $email = 'nuevo_'.bin2hex(random_bytes(4)).'@owomarket.com';

    $this->artisan('admin:create-super')
        ->expectsQuestion('Nombre del administrador', 'Gabriel')
        ->expectsQuestion('Correo electrónico', $email)
        ->expectsQuestion('Contraseña', 'Test_12345678')
        ->expectsQuestion('Confirma la contraseña', 'Otra_12345678')
        ->assertFailed();

    expect(User::where('email', $email)->exists())->toBeFalse();
});

test('rechaza un correo con formato inválido', function () {
    $this->artisan('admin:create-super')
        ->expectsQuestion('Nombre del administrador', 'Gabriel')
        ->expectsQuestion('Correo electrónico', 'esto-no-es-un-correo')
        ->assertFailed();
});
