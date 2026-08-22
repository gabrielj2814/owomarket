<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\ValidateAndConsumeSsoTokenUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerSsoToken;

/**
 * Hallazgos A8 y C5.
 *
 * A8: `ValidateAndConsumeSsoTokenUseCase` **recibia `$currentDomain` y nunca lo usaba**, y
 * el `target_domain` que se persiste al generar el token jamas se comparaba. El dueno de
 * la tienda A pedia un token legitimo para su tienda y lo abria en
 * `https://tiendaB.owomarket.com/auth/sso-consume?token=...`: el token era valido, asi que
 * quedaba logueado en una tienda ajena. Rotura completa del aislamiento multi-tenant.
 *
 * C5: leer el token, comprobar `used_at` y escribirlo eran tres sentencias sueltas, sin
 * transaccion ni `UPDATE ... WHERE used_at IS NULL`, asi que el mismo enlace se podia
 * consumir dos veces por carrera.
 */
beforeEach(function () {
    // El consumo del token sincroniza al cliente en la base del inquilino.
    if (! Schema::hasTable('customers')) {
        (require base_path('database/migrations/tenant/2025_10_28_144201_create_customers.php'))->up();
    }
    if (! Schema::hasColumn('customers', 'central_uuid')) {
        (require base_path('database/migrations/tenant/2026_08_19_000002_add_central_uuid_to_customers.php'))->up();
    }

    $this->customer = CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Ana Pérez',
        'email' => 'ana_'.bin2hex(random_bytes(4)).'@example.com',
        'password' => bcrypt('Password123!'),
        'is_active' => true,
    ]);

    $this->crearToken = function (?string $targetDomain): CentralCustomerSsoToken {
        return CentralCustomerSsoToken::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'token' => (string) Str::random(64),
            'target_domain' => $targetDomain,
            'expires_at' => now()->addMinutes(5),
            'used_at' => null,
        ]);
    };

    $this->useCase = app(ValidateAndConsumeSsoTokenUseCase::class);
});

// A8: el escenario exacto del hallazgo.
test('un token emitido para una tienda no sirve en otra', function () {
    $token = ($this->crearToken)('tienda-a.owomarket.com');

    expect(fn () => $this->useCase->execute($token->token, 'tienda-b.owomarket.com'))
        ->toThrow(Exception::class, 'no es válido para esta tienda');
});

test('un token emitido para una tienda sí sirve en esa tienda', function () {
    $token = ($this->crearToken)('tienda-a.owomarket.com');

    $resultado = $this->useCase->execute($token->token, 'tienda-a.owomarket.com');

    expect($resultado['central_customer']['id'])->toBe($this->customer->id);
});

// C5: el segundo consumo debe fallar, y el token debe quedar marcado una sola vez.
test('un token no se puede consumir dos veces', function () {
    $token = ($this->crearToken)('tienda-a.owomarket.com');

    $this->useCase->execute($token->token, 'tienda-a.owomarket.com');

    expect(fn () => $this->useCase->execute($token->token, 'tienda-a.owomarket.com'))
        ->toThrow(Exception::class);

    expect(CentralCustomerSsoToken::where('token', $token->token)->value('used_at'))->not->toBeNull();
});

test('un token caducado no se consume', function () {
    $token = ($this->crearToken)('tienda-a.owomarket.com');
    $token->update(['expires_at' => now()->subMinute()]);

    expect(fn () => $this->useCase->execute($token->token, 'tienda-a.owomarket.com'))
        ->toThrow(Exception::class);

    // Y no debe quedar marcado como usado: no llego a consumirse.
    expect(CentralCustomerSsoToken::where('token', $token->token)->value('used_at'))->toBeNull();
});
