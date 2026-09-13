<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\CentralCustomer\Application\Service\ClaimResponseWindow;
use Src\CentralCustomer\Application\UseCases\AutoResolveStaleReturnsUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

/**
 * El reloj que la tienda tiene que ver (subsistema 5, vista 2 de
 * `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * **El dato que hace que la pantalla se use.** `returns:auto-resolve` resuelve a favor del
 * comprador lo que la tienda no contesta en plazo; sin una cuenta atrás, el comerciante no sabe
 * que hay un reloj corriendo y descubre la reclamación cuando ya le ha revertido la venta y le
 * ha bajado el nivel de reputación a bajo.
 *
 * Lo que se vigila aquí es **que la cuenta atrás y el comando digan lo mismo**. Si divergen, la
 * pantalla promete días que el comando no respeta, y una cuenta atrás en la que no se puede
 * confiar es peor que no tener ninguna.
 */
beforeEach(function () {
    $this->owner = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Comerciante',
        'email' => 'owner_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);

    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda del Reloj',
        'slug' => 'reloj-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    // La propiedad se resuelve por `tenant_users`, no por una columna en `tenants`.
    $this->tenant->users()->attach($this->owner->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);
});

function reclamacionAbierta(Tenant $tenant, array $overrides = []): CustomerReturnRequest
{
    // `created_at` va DESPUÉS de crear: Eloquent pisa los timestamps al insertar, así que
    // pasarlo en `create()` no envejece nada.
    $creadaEl = $overrides['created_at'] ?? null;
    unset($overrides['created_at']);

    $reclamacion = CustomerReturnRequest::create(array_merge([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(4)),
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'cliente@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto roto',
        'amount' => 100.0,
        'tenant_id' => $tenant->id,
        'reason' => 'defectuoso',
        'description' => 'Llegó roto.',
        'status' => 'requested',
    ], $overrides));

    if ($creadaEl !== null) {
        $reclamacion->forceFill(['created_at' => $creadaEl])->saveQuietly();
    }

    return $reclamacion->fresh();
}

it('cada reclamación abierta dice cuántos días le quedan', function () {
    reclamacionAbierta($this->tenant, ['created_at' => now()->subDays(2)]);

    $fila = $this->actingAs($this->owner)
        ->getJson("/tenant/owner/api/returns/{$this->tenant->id}")
        ->assertOk()
        ->json('data.0');

    // Plazo de 5 días, lleva 2: quedan 3.
    expect($fila['days_left'])->toBe(3)
        ->and($fila['deadline_at'])->not->toBeNull();
});

it('una reclamación ya resuelta no muestra cuenta atrás', function () {
    // En una resuelta, una cuenta atrás no significa nada y solo confunde sobre si aún se
    // puede hacer algo.
    reclamacionAbierta($this->tenant, ['status' => 'approved', 'resolved_by' => 'merchant']);

    $fila = $this->actingAs($this->owner)
        ->getJson("/tenant/owner/api/returns/{$this->tenant->id}")
        ->assertOk()
        ->json('data.0');

    expect($fila['is_open'])->toBeFalse()
        ->and($fila['days_left'])->toBeNull()
        ->and($fila['deadline_at'])->toBeNull();
});

it('la cuenta atrás y el reloj usan el mismo plazo configurado', function () {
    // EL TEST QUE IMPORTA. Si la pantalla y el comando leyeran plazos distintos, el
    // comerciante perdería la venta después de que le dijéramos que tenía tiempo.
    CentralSetting::create([
        'id' => (string) Str::uuid(),
        'group' => 'payment',
        'key' => 'central_claim_response_days',
        'value' => '10',
    ]);

    // Con 10 días de plazo, una de 8 días todavía tiene 2 y NO la resuelve el comando.
    reclamacionAbierta($this->tenant, ['created_at' => now()->subDays(8)]);

    $fila = $this->actingAs($this->owner)
        ->getJson("/tenant/owner/api/returns/{$this->tenant->id}")
        ->assertOk()
        ->json('data.0');

    expect($fila['days_left'])->toBe(2);
    expect(app(AutoResolveStaleReturnsUseCase::class)->execute())->toBe(0);
});

it('una reclamación vencida muestra cero días, no un número negativo', function () {
    reclamacionAbierta($this->tenant, ['created_at' => now()->subDays(30)]);

    $fila = $this->actingAs($this->owner)
        ->getJson("/tenant/owner/api/returns/{$this->tenant->id}")
        ->assertOk()
        ->json('data.0');

    expect($fila['days_left'])->toBe(0);
});

it('sin ajuste configurado el plazo por defecto son cinco días', function () {
    expect(app(ClaimResponseWindow::class)->days())->toBe(5);
});

it('la pantalla de reclamaciones lista sólo las tiendas propias', function () {
    $ajena = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Ajena',
        'slug' => 'ajena-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $respuesta = $this->actingAs($this->owner)
        ->get("/tenant/owner/backoffice/{$this->owner->id}/returns")
        ->assertOk();

    $props = $respuesta->viewData('page')['props'];
    $ids = collect($props['tenants'])->pluck('id')->all();

    expect($ids)->toContain($this->tenant->id)
        ->and($ids)->not->toContain($ajena->id)
        ->and($props['response_days'])->toBe(5);
});
