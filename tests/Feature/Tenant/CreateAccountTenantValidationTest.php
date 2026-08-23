<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Src\Tenant\Infrastructure\Eloquent\Models\User;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    // El alta lleva throttle:altas desde A6 (3/hora por IP). Sin esto, los tests de este
    // fichero se cortan entre ellos.
    Illuminate\Support\Facades\RateLimiter::clear('altas');
    cache()->flush();

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);
});

test('POST /tenant/create/account fails validation when required fields are missing', function () {
    $response = $this->postJson('/tenant/create/account', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'status',
            'code',
            'message',
            'errors' => [
                'name',
                'email',
                'phone',
                'password',
                'store_name',
                'tenant_name',
            ],
        ]);
});

test('POST /tenant/create/account fails validation with duplicate email', function () {
    $email = 'existing_tenant_owner_'.bin2hex(random_bytes(3)).'@owomarket.com';

    User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Owner Existente',
        'email' => $email,
        'password' => bcrypt('Password123!'),
        'type' => 'tenant_owner',
        'phone' => '04121234567',
        'avatar' => 'https://via.placeholder.com/150',
        'is_active' => true,
    ]);

    $payload = [
        'name' => 'Comercio Duplicado',
        'email' => $email,
        'phone' => '04121234567',
        'password' => 'SecurePass123!',
        'confirmPassword' => 'SecurePass123!',
        'store_name' => 'Comercio Nuevo Unico '.bin2hex(random_bytes(3)),
        'tenant_name' => 'comercio-nuevo-'.bin2hex(random_bytes(3)).'.owomarket.local',
    ];

    $response = $this->postJson('/tenant/create/account', $payload);

    $response->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'Este correo electrónico ya se encuentra registrado.');
});

test('POST /tenant/create/account fails validation with mismatching password confirmation', function () {
    $payload = [
        'name' => 'Comercio Pass Test',
        'email' => 'unique_'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04121234567',
        'password' => 'SecurePass123!',
        'confirmPassword' => 'DifferentPass456!',
        'store_name' => 'Comercio Pass '.bin2hex(random_bytes(3)),
        'tenant_name' => 'comercio-pass-'.bin2hex(random_bytes(3)).'.owomarket.local',
    ];

    $response = $this->postJson('/tenant/create/account', $payload);

    $response->assertStatus(422)
        ->assertJsonPath('errors.confirmPassword.0', 'Las contraseñas no coinciden.');
});

/*
 * Hallazgo S2 — cuarto hermano de A4.
 *
 * Este formulario pedia 'min:8|max:72' y nada mas, mientras el registro de comprador ya
 * exigia mayuscula, minuscula, digito y simbolo. O sea que el alta de COMERCIANTE —quien
 * controla un catalogo, sus pedidos y sus liquidaciones— tenia la regla mas floja de las
 * cuatro. A4 llego al registro, al reset y al perfil, y se salto esta.
 */
test('el alta de comerciante exige la misma contrasena que el resto (S2)', function () {
    $this->postJson('/tenant/create/account', [
        'name' => 'Comerciante Debil',
        'email' => 'debil'.bin2hex(random_bytes(3)).'@example.com',
        'phone' => '04121234567',
        'store_name' => 'Tienda Debil',
        'password' => 'aaaaaaaa',
        'confirmPassword' => 'aaaaaaaa',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

/*
 * Hallazgo S1. Tras crear la cuenta, la pagina redirige a esta URL. Antes apuntaba a
 * '/auth/login-staff', que es el NOMBRE de la ruta usado como si fuera la URL: daba 404, y
 * el comerciante acababa el alta —con su tienda y su base de datos ya creadas— en una
 * pagina de error, creyendo que habia fallado.
 *
 * Si alguien cambia esa ruta, este test cae y obliga a mirar la redireccion del alta.
 */
test('la URL a la que redirige el alta existe (S1)', function () {
    $this->get('http://owomarket.local/auth/login')->assertStatus(200);
    $this->get('http://owomarket.local/auth/login-staff')->assertNotFound();
});
