<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Src\Monetization\Application\UseCases\ConfirmOrderDeliveryUseCase;
use Src\Monetization\Application\UseCases\DeclareOrderDeliveredUseCase;
use Src\Monetization\Application\UseCases\ReleaseUnconfirmedDeliveriesUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Subsistema 3 de `planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`.
 *
 * El agujero que cierra: habia DOS caminos por los que un pedido llegaba a `delivered`
 * --`DeliverOrderUseCase` y `MarkShipmentAsDeliveredUseCase`--, los dos expuestos en
 * `routes/tenantApi.php`, y los dos liberaban la comision. Es decir: **el comerciante
 * declaraba su propia entrega y con ello hacia retirable su propio dinero.**
 *
 * A partir de aqui declarar la entrega solo arranca un reloj. Liberar tiene dos causas
 * legitimas y ninguna es el comerciante: que el comprador confirme, o que venza el plazo.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Entrega',
        'slug' => 'entrega-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->customerId = (string) Str::uuid();
    $this->centralOrderId = (string) Str::uuid();
});

function comisionEntregable(Tenant $tenant, ?string $centralOrderId): PlatformCommission
{
    return PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => (string) Str::uuid(),
        'central_order_id' => $centralOrderId,
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'order_total' => 100.0,
        'commission_rate' => 8.00,
        'commission_amount' => 8.00,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'status' => 'pending',
        'released_at' => null,
        'payment_gateway' => 'pago_movil',
    ]);
}

/** Deja el expediente como si el comerciante hubiera declarado la entrega hace `$dias` dias. */
function expedienteDeclarado(Tenant $tenant, PlatformCommission $comision, ?string $customerId, int $dias = 0): OrderDeliveryConfirmation
{
    return OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $tenant->id,
        'order_id' => $comision->order_id,
        'central_order_id' => $comision->central_order_id,
        'customer_id' => $customerId,
        'declared_delivered_at' => now()->subDays($dias),
    ]);
}

it('declarar la entrega NO libera el dinero: solo arranca el reloj', function () {
    // EL TEST QUE IMPORTA. Si se pone verde con `released_at` puesto, alguien ha vuelto a
    // enchufar la liberacion al camino del comerciante y el agujero esta abierto otra vez.
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);

    app(DeclareOrderDeliveredUseCase::class)->execute($comision->order_id);

    expect($comision->fresh()->released_at)->toBeNull();

    $expediente = OrderDeliveryConfirmation::where('order_id', $comision->order_id)->first();
    expect($expediente)->not->toBeNull()
        ->and($expediente->declared_delivered_at)->not->toBeNull()
        ->and($expediente->released_at)->toBeNull();
});

it('declarar dos veces no mueve la fecha de la primera', function () {
    // Si la moviera, el comerciante podria aplazar la liberacion automatica indefinidamente
    // reenviando la declaracion, y el plazo dejaria de ser un plazo.
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    $expediente = expedienteDeclarado($this->tenant, $comision, $this->customerId, dias: 5);
    $original = $expediente->declared_delivered_at;

    app(DeclareOrderDeliveredUseCase::class)->execute($comision->order_id);

    expect($expediente->fresh()->declared_delivered_at->toIso8601String())
        ->toBe($original->toIso8601String());
});

it('la confirmación del comprador sí libera el dinero', function () {
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId);

    app(ConfirmOrderDeliveryUseCase::class)->execute($comision->order_id, $this->customerId);

    expect($comision->fresh()->released_at)->not->toBeNull();

    $expediente = OrderDeliveryConfirmation::where('order_id', $comision->order_id)->first();
    expect($expediente->confirmed_at)->not->toBeNull()
        ->and($expediente->released_by)->toBe('customer');
});

it('nadie más puede confirmar un pedido ajeno', function () {
    // Frontera de confianza: sin esto, cualquiera con un identificador de pedido libera el
    // dinero de una tienda cuando quiera.
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId);

    expect(fn () => app(ConfirmOrderDeliveryUseCase::class)
        ->execute($comision->order_id, (string) Str::uuid()))
        ->toThrow(Exception::class);

    expect($comision->fresh()->released_at)->toBeNull();
});

it('no se puede confirmar lo que la tienda no ha declarado todavía', function () {
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $comision->order_id,
        'customer_id' => $this->customerId,
        'declared_delivered_at' => null,
    ]);

    expect(fn () => app(ConfirmOrderDeliveryUseCase::class)
        ->execute($comision->order_id, $this->customerId))
        ->toThrow(Exception::class);

    expect($comision->fresh()->released_at)->toBeNull();
});

it('confirmar dos veces no vuelve a liberar', function () {
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId);

    $caso = app(ConfirmOrderDeliveryUseCase::class);
    $caso->execute($comision->order_id, $this->customerId);
    $liberadoEn = $comision->fresh()->released_at;

    expect(fn () => $caso->execute($comision->order_id, $this->customerId))
        ->toThrow(Exception::class);

    // La fecha de liberacion es el rastro de cuando el dinero paso a ser reclamable. Moverla
    // reescribiria la historia.
    expect($comision->fresh()->released_at->toIso8601String())->toBe($liberadoEn->toIso8601String());
});

it('el silencio del comprador libera al vencer el plazo', function () {
    // Sin esto el subsistema seria una trampa: un comprador que recibe su paquete y no vuelve
    // a entrar dejaria el dinero de la tienda congelado para siempre.
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId, dias: 8);

    $liberadas = app(ReleaseUnconfirmedDeliveriesUseCase::class)->execute();

    expect($liberadas)->toBe(1)
        ->and($comision->fresh()->released_at)->not->toBeNull();

    $expediente = OrderDeliveryConfirmation::where('order_id', $comision->order_id)->first();
    // `timeout` y no `customer`: es lo unico que dira, cuando haya datos, que porcentaje de
    // compradores confirma de verdad --y por tanto si siete dias es el plazo correcto--.
    expect($expediente->released_by)->toBe('timeout')
        ->and($expediente->confirmed_at)->toBeNull();
});

it('dentro del plazo no se libera nada', function () {
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId, dias: 3);

    expect(app(ReleaseUnconfirmedDeliveriesUseCase::class)->execute())->toBe(0);
    expect($comision->fresh()->released_at)->toBeNull();
});

it('el plazo es configurable', function () {
    CentralSetting::create([
        'group' => 'payment',
        'key' => 'central_delivery_confirmation_days',
        'value' => '2',
    ]);

    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId, dias: 3);

    expect(app(ReleaseUnconfirmedDeliveriesUseCase::class)->execute())->toBe(1);
});

it('una entrega ya confirmada no la vuelve a liberar el plazo', function () {
    $comision = comisionEntregable($this->tenant, $this->centralOrderId);
    expedienteDeclarado($this->tenant, $comision, $this->customerId, dias: 8);

    app(ConfirmOrderDeliveryUseCase::class)->execute($comision->order_id, $this->customerId);

    expect(app(ReleaseUnconfirmedDeliveriesUseCase::class)->execute())->toBe(0);
    expect(OrderDeliveryConfirmation::where('order_id', $comision->order_id)->first()->released_by)
        ->toBe('customer');
});

it('un pedido sin comisión no abre expediente', function () {
    // Los pedidos anteriores a la monetizacion no generan dinero que liberar, asi que no hay
    // nada que confirmar.
    $sinComision = (string) Str::uuid();

    expect(app(DeclareOrderDeliveredUseCase::class)->execute($sinComision))->toBeFalse();
    expect(OrderDeliveryConfirmation::where('order_id', $sinComision)->exists())->toBeFalse();
});
