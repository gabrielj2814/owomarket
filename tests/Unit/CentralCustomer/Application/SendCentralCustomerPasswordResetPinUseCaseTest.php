<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\SendCentralCustomerPasswordResetPinUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

uses(Tests\TestCase::class);

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('central_customer_password_resets')) {
        (require base_path('database/migrations/2026_08_19_000009_create_central_customer_password_resets_table.php'))->up();
    }
});

test('SendCentralCustomerPasswordResetPinUseCase generates 6-digit PIN and token for valid email', function () {
    $email = 'pedro_'.bin2hex(random_bytes(3)).'@example.com';
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Pedro Perez',
        'email' => $email,
        'password' => 'secret123',
    ]);

    $useCase = new SendCentralCustomerPasswordResetPinUseCase;
    $result = $useCase->execute($email);

    expect($result['success'])->toBeTrue();
    expect($result['email'])->toBe($email);
    expect($result['pin_code'])->toHaveLength(6);

    $record = CentralCustomerPasswordReset::where('email', $email)->first();
    expect($record)->not->toBeNull();
    expect($record->pin_code)->toBe($result['pin_code']);
    expect($record->expires_at)->toBeGreaterThan(now());
});

/*
 * Hallazgo A3. Este test decia lo contrario: exigia un 404 «No existe una cuenta
 * registrada con este correo». Eso era exactamente la fuga — el que pregunta se entera
 * de que cuentas hay. Ahora exige lo inverso, que las dos salidas sean indistinguibles.
 */
test('un correo sin cuenta responde igual que uno con cuenta y no crea nada (A3)', function () {
    $inexistente = 'nonexistent@example.com';

    $useCase = new SendCentralCustomerPasswordResetPinUseCase;
    $resultado = $useCase->execute($inexistente);

    expect($resultado['success'])->toBeTrue();
    expect($resultado['email'])->toBe($inexistente);

    // Lo que no debe pasar: ni PIN generado ni registro en la tabla.
    expect($resultado)->not->toHaveKey('pin_code');
    expect(CentralCustomerPasswordReset::where('email', $inexistente)->exists())->toBeFalse();
});

test('el mensaje es identico exista o no la cuenta (A3)', function () {
    $email = 'a3_'.bin2hex(random_bytes(3)).'@example.com';
    CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Cliente A3',
        'email' => $email,
        'password' => 'secret123',
    ]);

    $useCase = new SendCentralCustomerPasswordResetPinUseCase;

    // Si estos dos textos divergen, vuelve la enumeracion: no hace falta el codigo HTTP,
    // basta con leer la respuesta.
    expect($useCase->execute($email)['message'])
        ->toBe($useCase->execute('no.existe.jamas@example.com')['message']);
});
