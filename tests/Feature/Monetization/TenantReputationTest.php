<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Application\Service\TenantReputation;
use Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Reputación de la tienda (subsistema 5, fase C).
 *
 * **No se guarda en ninguna columna, y es deliberado:** un nivel almacenado necesita
 * disparadores de recálculo, y un nivel desfasado es un porcentaje de reserva equivocado — o
 * sea, dinero mal retenido. Derivarlo son dos cuentas y se usa donde eso es barato.
 *
 * Lo que se vigila aquí no es la etiqueta: es que **el nivel mueva dinero**. Una insignia que
 * no toca el flujo de caja le da igual a un comerciante; lo que le importa es cuánto se le
 * retiene de cada venta.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Reputación',
        'slug' => 'rep-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->reputacion = app(TenantReputation::class);
});

function entregasLimpias(Tenant $tenant, int $cuantas): void
{
    for ($i = 0; $i < $cuantas; $i++) {
        OrderDeliveryConfirmation::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'order_id' => (string) Str::uuid(),
            'declared_delivered_at' => now()->subDays(10),
            'released_at' => now()->subDays(3),
            'released_by' => 'customer',
        ]);
    }
}

function silencio(Tenant $tenant, int $diasAtras = 5): CustomerReturnRequest
{
    return CustomerReturnRequest::create([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-S',
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'c@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'P',
        'tenant_id' => $tenant->id,
        'reason' => 'defectuoso',
        'description' => 'roto',
        'status' => 'approved',
        'resolved_by' => 'timeout',
        'resolved_at' => now()->subDays($diasAtras),
    ]);
}

it('una tienda nueva entra en medio', function () {
    // Una tienda sin historial no es buena: es desconocida, y ahí está el riesgo real.
    expect($this->reputacion->level($this->tenant->id))->toBe(TenantReputation::MEDIO);
});

it('el historial limpio sube a alto', function () {
    entregasLimpias($this->tenant, 10);

    expect($this->reputacion->level($this->tenant->id))->toBe(TenantReputation::ALTO);
});

it('con entregas de sobra pero un silencio, cae a bajo', function () {
    // EL TEST QUE IMPORTA: la señal es «reclamaciones sin responder», y pesa más que cualquier
    // historial. Ignorar a un comprador no se compensa vendiendo mucho.
    entregasLimpias($this->tenant, 50);
    silencio($this->tenant);

    expect($this->reputacion->level($this->tenant->id))->toBe(TenantReputation::BAJO);
});

it('un silencio viejo deja de pesar', function () {
    // Sin caducidad, una tienda que falló una vez quedaría en `bajo` para siempre. Y una
    // sanción de la que no se puede salir no corrige comportamiento: solo expulsa.
    entregasLimpias($this->tenant, 10);
    silencio($this->tenant, diasAtras: 120);

    expect($this->reputacion->level($this->tenant->id))->toBe(TenantReputation::ALTO);
});

it('con deuda no se llega a alto, pero tampoco se cae a bajo', function () {
    // Dos reglas que parecían incompatibles y no lo son: el nivel mide comportamiento, la
    // deuda se paga vendiendo — y para vender hace falta poder cobrar.
    entregasLimpias($this->tenant, 20);

    PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-D',
        'order_total' => 100.0,
        'commission_rate' => 8.0,
        'commission_amount' => 8.0,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'status' => 'refunded',   // reembolsada: ya no cuenta como ganancia
        'released_at' => now()->subDays(30),
        'payment_gateway' => 'pago_movil',
    ]);
    CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-'.strtoupper(Str::random(6)),
        'tenant_id' => $this->tenant->id,
        'type' => 'payout',
        'total_orders_count' => 1,
        'gross_sales_amount' => 4600.0,   // pero ya se la había retirado
        'commission_amount' => 0.0,
        'net_amount' => 4600.0,
        'currency' => 'VES',
        'status' => 'settled',
    ]);

    expect($this->reputacion->level($this->tenant->id))->toBe(TenantReputation::MEDIO);
});

it('el nivel decide cuánto se retiene', function () {
    // La pieza que convierte la reputación en algo que el comerciante siente. Sin esto es una
    // insignia que le da igual.
    expect($this->reputacion->reservePercent($this->tenant->id))->toBe(10.0);

    entregasLimpias($this->tenant, 10);
    expect($this->reputacion->reservePercent($this->tenant->id))->toBe(5.0);

    silencio($this->tenant);
    expect($this->reputacion->reservePercent($this->tenant->id))->toBe(20.0);
});

it('liberar una venta retiene según el nivel', function () {
    // De punta a punta: el nivel llega hasta el dinero apartado de verdad.
    entregasLimpias($this->tenant, 10);   // -> alto, 5%

    $venta = PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-R',
        'order_total' => 100.0,
        'commission_rate' => 8.0,
        'commission_amount' => 8.0,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'status' => 'pending',
        'released_at' => null,
        'payment_gateway' => 'pago_movil',
    ]);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);

    // 92 USD de parte del comerciante al 5%, no al 10% de una tienda cualquiera.
    expect($venta->fresh()->reserve_amount)->toBe(4.60);
});

it('el progreso dice qué le falta para subir', function () {
    // Un nivel que baja sin decir por qué ni cómo se recupera no corrige a nadie: empuja a
    // abrir otra tienda con otro nombre.
    entregasLimpias($this->tenant, 4);

    $progreso = $this->reputacion->progress($this->tenant->id);

    expect($progreso['level'])->toBe(TenantReputation::MEDIO)
        ->and($progreso['deliveries'])->toBe(4)
        ->and($progreso['deliveries_for_next'])->toBe(10)
        ->and($progreso['unanswered_claims'])->toBe(0)
        ->and($progreso['has_debt'])->toBeFalse();
});
