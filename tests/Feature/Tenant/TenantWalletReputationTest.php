<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;
use Src\Tenant\Infrastructure\Eloquent\Models\User;

/**
 * El nivel de reputación en la billetera (subsistema 5, fase C · vista 3 de
 * `planes/ESTADO_DEL_PROYECTO.md`).
 *
 * `TenantReputation::progress()` existía desde que se construyó la fase C **sin que ningún
 * endpoint lo expusiera**: el comerciante veía bajar su saldo disponible y no tenía forma de
 * saber que el motivo era su nivel.
 *
 * Lo que se vigila es que el porcentaje de retención viaje CON el nivel. Si la pantalla tuviera
 * su propia tabla de porcentajes, el día que cambien el comerciante leería un número que no es
 * el que se le aplica —y ese número es dinero suyo retenido—.
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
        'name' => 'Tienda Reputada',
        'slug' => 'reput-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->tenant->users()->attach($this->owner->id, ['id' => (string) Str::uuid(), 'role' => 'owner']);
});

it('la billetera dice el nivel y lo que se retiene en él', function () {
    $reputacion = $this->actingAs($this->owner)
        ->getJson('/tenant/owner/api/wallet-summary')
        ->assertOk()
        ->json('data.reputation');

    // Sin silencios, sin deuda y sin entregas suficientes: medio, que es el 10% que ya regía
    // para todos antes de que existieran los niveles.
    expect($reputacion['level'])->toBe('medio')
        // Comparado como float: al serializarse a JSON un 10.0 redondo viaja como `10`, que
        // es exactamente lo que recibe la pantalla.
        ->and((float) $reputacion['reserve_percent'])->toBe(10.0)
        ->and($reputacion['deliveries_for_next'])->toBe(10);
});

it('una reclamación resuelta por silencio baja el nivel y lo duplica la retención', function () {
    // EL TEST QUE IMPORTA: es la consecuencia que la pantalla de reclamaciones promete. Si no
    // se cumpliera, ese aviso sería una amenaza vacía.
    CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-X',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'cliente@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'amount' => 100.0,
        'tenant_id' => $this->tenant->id,
        'reason' => 'defectuoso',
        'description' => 'No respondida.',
        'status' => 'approved',
        'resolved_by' => 'timeout',
        'resolved_at' => now()->subDays(3),
    ]);

    $reputacion = $this->actingAs($this->owner)
        ->getJson('/tenant/owner/api/wallet-summary')
        ->assertOk()
        ->json('data.reputation');

    expect($reputacion['level'])->toBe('bajo')
        ->and((float) $reputacion['reserve_percent'])->toBe(20.0)
        ->and($reputacion['unanswered_claims'])->toBe(1);
});

it('un silencio de hace más de 90 días ya no pesa', function () {
    // Una sanción de la que no se puede salir no corrige comportamiento: solo expulsa.
    CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-Y',
        'tenant_order_id' => (string) Str::uuid(),
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'cliente@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
        'amount' => 100.0,
        'tenant_id' => $this->tenant->id,
        'reason' => 'defectuoso',
        'description' => 'No respondida.',
        'status' => 'approved',
        'resolved_by' => 'timeout',
        'resolved_at' => now()->subDays(120),
    ]);

    $reputacion = $this->actingAs($this->owner)
        ->getJson('/tenant/owner/api/wallet-summary')
        ->assertOk()
        ->json('data.reputation');

    expect($reputacion['level'])->toBe('medio')
        ->and($reputacion['unanswered_claims'])->toBe(0);
});

it('un usuario sin tiendas no recibe un nivel inventado', function () {
    $sinTiendas = User::create([
        'id' => (string) Str::uuid(),
        'name' => 'Sin Tiendas',
        'email' => 'vacio_'.Str::random(6).'@owomarket.com',
        'password' => bcrypt('password123'),
        'type' => 'tenant_owner',
        'is_active' => true,
    ]);

    $this->actingAs($sinTiendas)
        ->getJson('/tenant/owner/api/wallet-summary')
        ->assertOk()
        ->assertJsonPath('data.reputation', null);
});
