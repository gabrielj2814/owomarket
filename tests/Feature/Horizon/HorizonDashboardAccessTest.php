<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Src\User\Infrastructure\Eloquent\Models\User;

/*
|--------------------------------------------------------------------------
| Hallazgo N40 — quien puede ver el panel de Horizon
|--------------------------------------------------------------------------
|
| El panel no es solo telemetria: ensena la carga util de cada job —identificadores de
| pedidos, correos de clientes, el detalle de los fallos— y deja reintentar y borrar
| trabajos. Es una consola de operacion sobre el despacho de pedidos.
|
| Horizon lo trae abierto con una lista de correos VACIA, que fuera de `local` no deja
| pasar a nadie. Se ata al mismo rol que el resto del backoffice central para que dar de
| alta un superadministrador no exija acordarse de tocar el provider.
*/

/** Crea un usuario central del tipo pedido. `forceCreate` porque `type` ya no es asignable en masa. */
function usuarioCentral(string $type, bool $activo = true): User
{
    return User::forceCreate([
        'id' => (string) Str::uuid(),
        'name' => 'Usuario '.$type,
        'email' => Str::random(8).'@owomarket.local',
        'password' => bcrypt('EndAdmin_12345678'),
        'type' => $type,
        'is_active' => $activo,
    ]);
}

test('un superadministrador puede ver el panel', function () {
    expect(Gate::forUser(usuarioCentral('super_admin'))->allows('viewHorizon'))->toBeTrue();
});

test('un propietario de tienda no puede ver el panel', function () {
    // El caso que importa: tiene sesion en el backoffice, pero el panel ensena los pedidos
    // de TODAS las tiendas, no solo la suya.
    expect(Gate::forUser(usuarioCentral('tenant_owner'))->allows('viewHorizon'))->toBeFalse();
});

test('un cliente no puede ver el panel', function () {
    expect(Gate::forUser(usuarioCentral('customer'))->allows('viewHorizon'))->toBeFalse();
});

test('sin sesion no se puede ver el panel', function () {
    expect(Gate::allows('viewHorizon'))->toBeFalse();
});

test('un superadministrador desactivado tampoco puede', function () {
    // Desactivar la cuenta tiene que cerrar tambien esta puerta, no solo el login.
    expect(Gate::forUser(usuarioCentral('super_admin', activo: false))->allows('viewHorizon'))->toBeFalse();
});
