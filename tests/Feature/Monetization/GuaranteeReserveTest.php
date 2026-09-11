<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Application\UseCases\ReleaseOrderCommissionUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * El fondo de garantía (subsistema 4 de
 * `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`).
 *
 * Es lo que cierra el **hueco 2**: un comerciante al que se le reembolsa una venta cuyo dinero
 * ya retiró arrastra una deuda que solo se compensa si vuelve a vender. Reteniendo un
 * porcentaje de cada venta durante un tiempo, esa deuda deja de poder existir — hay con qué
 * pagarla sin perseguir a nadie.
 *
 * No es un mecanismo nuevo: es hacer **parcial** la retención que ya hacía
 * `TenantAvailableBalance`, que era todo o nada. Por eso la reserva se resta dentro de la
 * misma fórmula y no vive en una tabla aparte: el saldo de una tienda tiene que salir de un
 * solo sitio.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Fondo',
        'slug' => 'fondo-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->balance = app(TenantAvailableBalance::class);
});

/** Una venta cobrada y entregada hace tiempo, pero todavía sin liberar. */
function ventaSinLiberar(Tenant $tenant, float $total = 100.0, float $tasa = 50.0): PlatformCommission
{
    return PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => (string) Str::uuid(),
        'central_order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'order_total' => $total,
        'commission_rate' => 8.00,
        'commission_amount' => round($total * 0.08, 2),
        'currency' => 'USD',
        'exchange_rate' => $tasa,
        'status' => 'pending',
        'released_at' => null,
        'payment_gateway' => 'pago_movil',
    ]);
}

it('liberar aparta el 10% como fondo de garantía', function () {
    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);

    $venta = $venta->fresh();

    // 92 USD de parte del comerciante; el 10% son 9.20.
    expect($venta->reserve_amount)->toBe(9.20)
        ->and($venta->released_at)->not->toBeNull()
        ->and($venta->reserve_until)->not->toBeNull();
});

it('el saldo retirable baja en el importe del fondo', function () {
    // EL TEST QUE IMPORTA: sin esto la reserva seria contabilidad decorativa. 92 USD a tasa
    // 50 son 4.600 Bs; reteniendo el 10%, retirables 4.140.
    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);
    // La ventana de garantia de la Fase 4b corre igualmente, asi que se retrasa la
    // liberacion para que la venta ya sea retirable y lo unico que reste sea el fondo.
    $venta->fresh()->update(['released_at' => now()->subDays(30)]);

    expect($this->balance->settleable($this->tenant->id))->toBe(4140.0);
});

it('cumplido el plazo, el fondo vuelve al saldo', function () {
    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);
    $venta->fresh()->update([
        'released_at' => now()->subDays(90),
        'reserve_until' => now()->subDay(),
    ]);

    // Los 4.600 enteros: la reserva no se pierde, solo espera.
    expect($this->balance->settleable($this->tenant->id))->toBe(4600.0);
});

it('el fondo se valora a la tasa congelada de su venta, no a la de hoy', function () {
    // Mismo principio que rige el resto del saldo: la plataforma retiene una parte de los
    // bolivares que recibio, no un importe revalorizado.
    $venta = ventaSinLiberar($this->tenant, total: 100.0, tasa: 200.0);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);
    $venta->fresh()->update(['released_at' => now()->subDays(30)]);

    // 92 × 200 = 18.400 menos el 10% (9.20 × 200 = 1.840) = 16.560.
    expect($this->balance->settleable($this->tenant->id))->toBe(16560.0);
});

it('el porcentaje y el plazo son configurables', function () {
    CentralSetting::create(['group' => 'payment', 'key' => 'central_guarantee_reserve_percent', 'value' => '25']);
    CentralSetting::create(['group' => 'payment', 'key' => 'central_guarantee_reserve_days', 'value' => '10']);

    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);

    expect($venta->fresh()->reserve_amount)->toBe(23.0);
});

it('un porcentaje de cero no retiene nada', function () {
    // Cero es legitimo: significa apagar el fondo. Y sin `reserve_until` puesto, porque una
    // reserva de cero con fecha solo seria ruido en la consulta del saldo.
    CentralSetting::create(['group' => 'payment', 'key' => 'central_guarantee_reserve_percent', 'value' => '0']);

    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);
    $venta->fresh()->update(['released_at' => now()->subDays(30)]);

    expect($venta->fresh()->reserve_amount)->toBe(0.0)
        ->and($venta->fresh()->reserve_until)->toBeNull()
        ->and($this->balance->settleable($this->tenant->id))->toBe(4600.0);
});

it('una nota de crédito no genera reserva', function () {
    // Una nota de credito lleva la parte del comerciante en NEGATIVO. Retener un porcentaje
    // de una deuda no significa nada, y ademas restaria al reves: aumentaria el saldo de
    // quien debe dinero.
    $nota = PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-NOTA',
        'order_total' => -100.0,
        'commission_rate' => 8.00,
        'commission_amount' => -8.0,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'status' => 'pending',
        'released_at' => null,
        'payment_gateway' => 'pago_movil',
    ]);

    app(ReleaseOrderCommissionUseCase::class)->execute($nota->order_id);

    expect($nota->fresh()->reserve_amount)->toBe(0.0)
        ->and($nota->fresh()->reserve_until)->toBeNull();
});

it('liberar dos veces no vuelve a apartar reserva', function () {
    $venta = ventaSinLiberar($this->tenant);

    $caso = app(ReleaseOrderCommissionUseCase::class);
    $caso->execute($venta->order_id);
    $primera = $venta->fresh()->reserve_until;

    expect($caso->execute($venta->order_id))->toBe(0);
    expect($venta->fresh()->reserve_until->toIso8601String())->toBe($primera->toIso8601String());
});

it('la wallet enseña el fondo por separado', function () {
    // Al comerciante no puede bajarle el saldo sin decirle por que. El fondo se muestra
    // aparte de los otros dos motivos de retencion, que son de naturaleza distinta.
    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);
    $venta->fresh()->update(['released_at' => now()->subDays(30)]);

    $desglose = $this->balance->breakdown($this->tenant->id);

    expect($desglose['retenido_fondo_bs'])->toBe(460.0)
        ->and($desglose['disponible_bs'])->toBe(4600.0);
});

it('el retiro no puede sacar el fondo', function () {
    // La prueba de que esto no es cosmetico: es la consulta que autoriza dinero real.
    $venta = ventaSinLiberar($this->tenant);

    app(ReleaseOrderCommissionUseCase::class)->execute($venta->order_id);
    $venta->fresh()->update(['released_at' => now()->subDays(30)]);

    expect($this->balance->requestable($this->tenant->id))->toBe(4140.0);
});
