<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\CentralCustomer\Application\UseCases\AutoResolveStaleReturnsUseCase;
use Src\CentralCustomer\Application\UseCases\ResolveReturnRequestUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Application\Service\TenantAvailableBalance;
use Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Reclamaciones: resolución, reloj y dinero (subsistema 5, fases A y B).
 *
 * La tabla `customer_return_requests` existía desde agosto y **el cliente ya creaba solicitudes
 * que nadie resolvía**: entraban y se quedaban ahí para siempre. Esto es lo que le faltaba para
 * ser un expediente y no un buzón.
 *
 * El hallazgo que recortó el subsistema: **la maquinaria del dinero ya estaba completa**.
 * Aprobar una reclamación es revertir la comisión, que ya existe y está verificado desde
 * `CreditNoteBalanceTest`. El fondo de garantía tampoco se «cobra» aparte — al retener un
 * porcentaje de cada venta reduce cuánto pudo llevarse el comerciante, y por tanto reduce el
 * agujero por construcción.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Reclamo',
        'slug' => 'recl-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->balance = app(TenantAvailableBalance::class);
    $this->orderId = (string) Str::uuid();
});

function ventaReclamable(Tenant $tenant, string $orderId, float $total = 100.0, float $tasa = 50.0): PlatformCommission
{
    return PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => $orderId,
        'central_order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'order_total' => $total,
        'commission_rate' => 8.00,
        'commission_amount' => round($total * 0.08, 2),
        'currency' => 'USD',
        'exchange_rate' => $tasa,
        'status' => 'pending',
        'released_at' => now()->subDays(30),
        // Una venta liberada de verdad lleva su fondo de garantia apartado (subsistema 4): el
        // 10% de la parte del comerciante, retenido 60 dias. Se pone a mano porque aqui no se
        // esta probando la liberacion, pero omitirlo haria que estos tests midieran un saldo
        // que en produccion no existe.
        'reserve_amount' => round(($total - round($total * 0.08, 2)) * 0.10, 2),
        'reserve_until' => now()->addDays(30),
        'payment_gateway' => 'pago_movil',
    ]);
}

function reclamacion(Tenant $tenant, string $orderId, array $overrides = []): CustomerReturnRequest
{
    // `created_at` se aplica DESPUES de crear: Eloquent pisa los timestamps al insertar, asi
    // que pasarlo en `create()` no envejece nada y el reloj nunca encontraria una vencida.
    $creadaEl = $overrides['created_at'] ?? null;
    unset($overrides['created_at']);

    $reclamacion = CustomerReturnRequest::create(array_merge([
        'id' => (string) Str::uuid(),
        'order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-X',
        'tenant_order_id' => $orderId,
        'customer_id' => (string) Str::uuid(),
        'customer_email' => 'cliente@example.com',
        'product_id' => (string) Str::uuid(),
        'product_name' => 'Producto',
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

it('aprobar una reclamación revierte la venta', function () {
    // La maquinaria del dinero ya existía: aprobar es revertir. Aquí se comprueba que de
    // verdad se llama, no que exista.
    $venta = ventaReclamable($this->tenant, $this->orderId);
    expect($this->balance->settleable($this->tenant->id))->toBe(4140.0);   // 4.600 menos el 10% de fondo

    app(ResolveReturnRequestUseCase::class)->execute(reclamacion($this->tenant, $this->orderId)->id, aprobada: true);

    expect($venta->fresh()->status)->toBe('refunded')
        ->and($this->balance->settleable($this->tenant->id))->toBe(0.0);
});

it('rechazar no mueve dinero', function () {
    $venta = ventaReclamable($this->tenant, $this->orderId);

    app(ResolveReturnRequestUseCase::class)
        ->execute(reclamacion($this->tenant, $this->orderId)->id, aprobada: false, notas: 'El producto llegó bien.');

    expect($venta->fresh()->status)->toBe('pending')
        ->and($this->balance->settleable($this->tenant->id))->toBe(4140.0);
});

it('rechazar exige motivo', function () {
    // Sin él, el comprador ve su reclamación denegada y no sabe por qué.
    ventaReclamable($this->tenant, $this->orderId);
    $r = reclamacion($this->tenant, $this->orderId);

    expect(fn () => app(ResolveReturnRequestUseCase::class)->execute($r->id, aprobada: false))
        ->toThrow(Exception::class);

    expect($r->fresh()->status)->toBe('requested');
});

it('una reclamación ya resuelta no se resuelve dos veces', function () {
    // Sin esta guarda, el reloj podría resolver por silencio una reclamación que el
    // comerciante acababa de atender, y el comprador cobraría dos veces.
    ventaReclamable($this->tenant, $this->orderId);
    $r = reclamacion($this->tenant, $this->orderId);

    $caso = app(ResolveReturnRequestUseCase::class);
    $caso->execute($r->id, aprobada: true);

    expect(fn () => $caso->execute($r->id, aprobada: true))->toThrow(Exception::class);
});

it('el silencio de la tienda resuelve a favor del comprador', function () {
    // EL TEST QUE IMPORTA de la fase A. Sin el reloj, ignorar es la estrategia ganadora:
    // el comerciante no responde, el comprador se cansa y no pasa nada.
    ventaReclamable($this->tenant, $this->orderId);
    $r = reclamacion($this->tenant, $this->orderId, ['created_at' => now()->subDays(6)]);

    expect(app(AutoResolveStaleReturnsUseCase::class)->execute())->toBe(1);

    expect($r->fresh()->status)->toBe('approved')
        ->and($r->fresh()->resolved_by)->toBe('timeout');
});

it('dentro del plazo el reloj no toca nada', function () {
    ventaReclamable($this->tenant, $this->orderId);
    reclamacion($this->tenant, $this->orderId, ['created_at' => now()->subDays(2)]);

    expect(app(AutoResolveStaleReturnsUseCase::class)->execute())->toBe(0);
});

it('el plazo de respuesta es configurable', function () {
    CentralSetting::create(['group' => 'payment', 'key' => 'central_claim_response_days', 'value' => '1']);
    ventaReclamable($this->tenant, $this->orderId);
    reclamacion($this->tenant, $this->orderId, ['created_at' => now()->subDays(2)]);

    expect(app(AutoResolveStaleReturnsUseCase::class)->execute())->toBe(1);
});

it('resuelta por silencio queda distinguida de una respuesta real', function () {
    // No es contabilidad ociosa: «resuelta por silencio» es exactamente la señal con la que la
    // fase C calculará la reputación. Sin separarla, la tienda que ignora y la que atiende
    // puntúan igual.
    ventaReclamable($this->tenant, $this->orderId);
    $atendida = reclamacion($this->tenant, $this->orderId);

    app(ResolveReturnRequestUseCase::class)->execute($atendida->id, aprobada: true, resolvedBy: 'merchant');

    expect($atendida->fresh()->resolved_by)->toBe('merchant');
});

it('si la tienda ya se llevó el dinero, la plataforma pone la diferencia', function () {
    // EL TEST QUE IMPORTA de la fase B. Es el caso del hueco 2: se reembolsa una venta cuyo
    // importe ya salió en un retiro pagado.
    ventaReclamable($this->tenant, $this->orderId);

    CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-'.strtoupper(Str::random(6)),
        'tenant_id' => $this->tenant->id,
        'type' => 'payout',
        'total_orders_count' => 1,
        'gross_sales_amount' => 4140.0,   // se retiró todo lo retirable
        'commission_amount' => 0.0,
        'net_amount' => 4140.0,
        'currency' => 'VES',
        'status' => 'settled',
    ]);

    expect($this->balance->settleable($this->tenant->id))->toBe(0.0);

    $r = reclamacion($this->tenant, $this->orderId);
    app(ResolveReturnRequestUseCase::class)->execute($r->id, aprobada: true);

    // El saldo no podía absorber nada, así que los 5.000 Bs del reembolso los pone la
    // plataforma — hasta el tope, que por defecto son $200 a la tasa de la venta.
    expect($r->fresh()->platform_covered_amount)->toBeGreaterThan(0.0);
});

it('la cobertura de la plataforma no pasa del tope', function () {
    CentralSetting::create(['group' => 'payment', 'key' => 'central_claim_coverage_cap', 'value' => '10']);

    // Venta grande y saldo ya retirado: sin tope, la plataforma pondría los 46.000 Bs enteros.
    ventaReclamable($this->tenant, $this->orderId, total: 1000.0, tasa: 50.0);
    CommissionSettlement::create([
        'id' => (string) Str::uuid(),
        'settlement_number' => 'PAY-'.strtoupper(Str::random(6)),
        'tenant_id' => $this->tenant->id,
        'type' => 'payout',
        'total_orders_count' => 1,
        'gross_sales_amount' => 41400.0,
        'commission_amount' => 0.0,
        'net_amount' => 41400.0,
        'currency' => 'VES',
        'status' => 'settled',
    ]);

    $r = reclamacion($this->tenant, $this->orderId, ['amount' => 1000.0]);
    app(ResolveReturnRequestUseCase::class)->execute($r->id, aprobada: true);

    // $10 de tope × tasa 50 = 500 Bs, y ni un bolívar más.
    expect($r->fresh()->platform_covered_amount)->toBe(500.0);
});

it('si el saldo lo cubre, la plataforma no pone nada', function () {
    // El caso sano, y el más frecuente: la tienda no había retirado, así que su propio saldo
    // absorbe el reembolso entero.
    ventaReclamable($this->tenant, $this->orderId);

    $r = reclamacion($this->tenant, $this->orderId);
    app(ResolveReturnRequestUseCase::class)->execute($r->id, aprobada: true);

    expect($r->fresh()->platform_covered_amount)->toBe(0.0);
});
