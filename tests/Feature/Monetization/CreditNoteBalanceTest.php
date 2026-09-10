<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Application\UseCases\GenerateTenantCommissionSettlementUseCase;
use Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Que revertir una venta descuenta su importe UNA sola vez.
 *
 * Estos tests no acompanan a un arreglo: son una cerradura. `PLAN_REEMBOLSO_TRAS_RETIRO.md`
 * daba por hecho que la wallet no se enteraba de las reversiones, y proponia arreglarlo dando
 * `exchange_rate` y `released_at` a la nota de credito para que `TenantAvailableBalance` la
 * sumase. Medido, resulto falso y ademas peligroso: la wallet YA descuenta la reversion --por
 * otra via-- y hacer entrar la nota ahi cobraria la devolucion dos veces.
 *
 * Los dos mecanismos son distintos a proposito, y hay que entenderlo antes de tocar nada:
 *
 *   - **La wallet es un saldo corriente.** La reversion se expresa quitando la ganancia: el
 *     estado pasa a `refunded`/`waived`, que no estan en `ESTADOS_COBRADOS`, y la venta sale
 *     de `netEarnings()`. Si ademas se le habia pagado en un retiro, ese retiro **sigue
 *     restandose**, y esa resta huerfana ES la deuda. No hace falta nada mas.
 *
 *   - **Las liquidaciones son documentos de un periodo cerrado.** Una ya emitida no se
 *     reescribe, asi que la correccion tiene que llegar como una fila negativa en la
 *     siguiente. Para eso existe la nota de credito del hallazgo N16.
 *
 * Dos representaciones del mismo hecho, cada una correcta en su sitio. El test que de verdad
 * vigila es «la deuda se cuenta una sola vez»: si alguien vuelve a implementar aquella idea,
 * se pone rojo con un 0 donde deberia haber 4.600.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Nota',
        'slug' => 'nota-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->balance = app(TenantAvailableBalance::class);
});

/**
 * Una venta cobrada, entregada hace tiempo y por tanto retirable.
 *
 * `collected` y no `pending` a proposito: es lo que hace que `ReverseOrderCommissionUseCase`
 * la trate como ya liquidada y emita la nota de credito, que es el escenario del plan.
 */
function ventaCobrada(Tenant $tenant, float $total = 100.0, float $tasa = 50.0): PlatformCommission
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
        'status' => 'collected',
        'released_at' => now()->subDays(30),
        'payment_gateway' => 'pago_movil',
    ]);
}

/** Un retiro ya pagado al comerciante, en bolivares, que es como los cuenta la wallet. */
function retiroPagado(Tenant $tenant, float $bolivares): CommissionSettlement
{
    return CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-'.strtoupper(Str::random(6)),
        'tenant_id' => $tenant->id,
        'type' => 'payout',
        'total_orders_count' => 1,
        'gross_sales_amount' => $bolivares,
        'commission_amount' => 0.0,
        'net_amount' => $bolivares,
        'currency' => 'VES',
        'status' => 'settled',
    ]);
}

function revertir(PlatformCommission $venta, string $motivo = ReverseOrderCommissionUseCase::REASON_REFUNDED): void
{
    app(ReverseOrderCommissionUseCase::class)->execute($venta->order_id, $motivo);
}

it('reembolsar una venta la saca del saldo retirable', function () {
    $venta = ventaCobrada($this->tenant);

    // 92 USD de parte del comerciante (100 menos el 8% de comision) a la tasa de 50.
    expect($this->balance->settleable($this->tenant->id))->toBe(4600.0);

    revertir($venta);

    expect($this->balance->settleable($this->tenant->id))->toBe(0.0);
});

it('cancelar una venta la saca del saldo igual que reembolsarla', function () {
    $venta = ventaCobrada($this->tenant);

    revertir($venta, ReverseOrderCommissionUseCase::REASON_CANCELLED);

    expect($this->balance->settleable($this->tenant->id))->toBe(0.0);
});

it('la deuda de un reembolso tras un retiro ya pagado se cuenta una sola vez', function () {
    // EL TEST QUE VIGILA. Es el escenario entero del plan, de punta a punta.
    //
    // Si se pone rojo con 0.0, alguien le ha dado `exchange_rate` y `released_at` a la nota
    // de credito para que la wallet la sume. No lo arregles subiendo el numero esperado: lee
    // la cabecera de este fichero. La deuda ya estaba contada.

    // 1. Vende 100 USD a tasa 50: 4.600 Bs suyos.
    $venta = ventaCobrada($this->tenant);

    // 2. Se los retira enteros y la plataforma se los paga. Saldo a cero.
    retiroPagado($this->tenant, 4600.0);
    expect($this->balance->settleable($this->tenant->id))->toBe(0.0);

    // 3. Semanas despues el comprador reclama y se reembolsa. El dinero ya salio: la
    //    plataforma ha pagado dos veces y el comerciante le debe 4.600.
    revertir($venta);

    // La deuda no se ve --`settleable()` recorta en cero-- pero esta ahi dentro: la venta
    // salio de las ganancias y el retiro sigue restandose. Ese hueco es la deuda.
    expect($this->balance->settleable($this->tenant->id))->toBe(0.0);

    // 4. La tienda vuelve a vender, ahora 200 USD = 9.200 Bs.
    ventaCobrada($this->tenant, total: 200.0);

    // 9.200 menos los 4.600 que debia. Contada dos veces daria 0; sin contar, 9.200.
    expect($this->balance->settleable($this->tenant->id))->toBe(4600.0);
});

it('la nota de credito no la ve la wallet, y es correcto', function () {
    // La otra mitad de la cerradura, dicha sobre la nota misma en vez de sobre el saldo: nace
    // sin tasa, asi que `netEarnings()` --que exige `whereNotNull('exchange_rate')`-- no la
    // suma. No es un descuido que haya que reparar; es lo que evita el cobro doble.
    $venta = ventaCobrada($this->tenant);

    revertir($venta);

    $nota = PlatformCommission::where('tenant_id', $this->tenant->id)
        ->where('order_total', '<', 0)
        ->first();

    expect($nota)->not->toBeNull();
    expect((float) $nota->order_total)->toBe(-100.0);
    expect((float) $nota->commission_amount)->toBe(-8.0);
    expect($nota->status)->toBe('pending');
    expect($nota->settlement_id)->toBeNull();
    expect($nota->exchange_rate)->toBeNull();
    expect($nota->metadata['credit_note'])->toBeTrue();
});

it('la nota de credito si corrige la siguiente liquidacion', function () {
    // Y esta es la razon de que la nota exista: lo que la wallet resuelve quitando la
    // ganancia, la liquidacion --un documento ya emitido, que no se reescribe-- lo resuelve
    // con una fila negativa en la del periodo siguiente.
    $venta = ventaCobrada($this->tenant);

    revertir($venta);

    $liquidacion = app(GenerateTenantCommissionSettlementUseCase::class)
        ->execute($this->tenant->id, 'collection');

    // Solo entra la nota: la venta original quedo en `refunded`, fuera de las pendientes.
    expect($liquidacion->total_orders_count)->toBe(1);
    expect((float) $liquidacion->commission_amount)->toBe(-8.0);
    expect((float) $liquidacion->gross_sales_amount)->toBe(-100.0);
});
