<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Monetization\Application\UseCases\AttachShipmentEvidenceUseCase;
use Src\Monetization\Application\UseCases\ConfirmOrderDeliveryUseCase;
use Src\Monetization\Application\UseCases\GetOrderDeliveryStatusUseCase;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Subsistema 3, fase B: las evidencias de entrega.
 *
 * No liberan nada --eso sigue siendo cosa del comprador o del plazo, fase A-- pero cambian la
 * conversacion cuando hay discusion: sin evidencia, «lo envie» contra «no me llego» es la
 * palabra de uno contra la del otro y la plataforma no tiene con que decidir.
 *
 * Viven en el expediente CENTRAL para que las vean las tres partes. Si estuvieran en la base
 * de la tienda, ni el comprador ni el administrador podrian consultarlas, que es justo lo que
 * las haria inutiles.
 */
beforeEach(function () {
    Storage::fake('public');

    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Evidencia',
        'slug' => 'evi-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);

    $this->customerId = (string) Str::uuid();

    $this->comision = PlatformCommission::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => (string) Str::uuid(),
        'central_order_id' => (string) Str::uuid(),
        'order_number' => 'ORD-'.strtoupper(Str::random(6)),
        'order_total' => 100.0,
        'commission_rate' => 8.00,
        'commission_amount' => 8.00,
        'currency' => 'USD',
        'exchange_rate' => 50.0,
        'status' => 'pending',
        'payment_gateway' => 'pago_movil',
    ]);
});

function foto(string $nombre = 'paquete.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($nombre);
}

it('la tienda puede adjuntar evidencia de envío antes de declarar la entrega', function () {
    // El orden real de los hechos: primero se envia, despues llega. El expediente nace aqui,
    // y **sin arrancar el reloj**: enviar no es entregar.
    app(AttachShipmentEvidenceUseCase::class)->execute($this->comision->order_id, [foto()]);

    $expediente = OrderDeliveryConfirmation::where('order_id', $this->comision->order_id)->first();

    expect($expediente)->not->toBeNull()
        ->and($expediente->shipment_evidence)->toHaveCount(1)
        ->and($expediente->declared_delivered_at)->toBeNull();
});

it('adjuntar evidencia no libera dinero', function () {
    app(AttachShipmentEvidenceUseCase::class)->execute($this->comision->order_id, [foto()]);

    expect($this->comision->fresh()->released_at)->toBeNull();
});

it('las evidencias se acumulan, no se reemplazan', function () {
    // El comerciante sube la foto del paquete el lunes y el comprobante del transportista el
    // martes. Perder la primera al añadir la segunda seria una trampa.
    $caso = app(AttachShipmentEvidenceUseCase::class);
    $caso->execute($this->comision->order_id, [foto('lunes.jpg')]);
    $caso->execute($this->comision->order_id, [foto('martes.jpg')]);

    $expediente = OrderDeliveryConfirmation::where('order_id', $this->comision->order_id)->first();

    expect($expediente->shipment_evidence)->toHaveCount(2);
});

it('un pedido sin venta registrada no admite evidencia', function () {
    expect(fn () => app(AttachShipmentEvidenceUseCase::class)->execute((string) Str::uuid(), [foto()]))
        ->toThrow(Exception::class);
});

it('una entrega ya cerrada no admite más evidencias', function () {
    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $this->comision->order_id,
        'customer_id' => $this->customerId,
        'declared_delivered_at' => now()->subDay(),
        'released_at' => now(),
        'released_by' => 'customer',
    ]);

    expect(fn () => app(AttachShipmentEvidenceUseCase::class)->execute($this->comision->order_id, [foto()]))
        ->toThrow(Exception::class);
});

it('el comprador puede confirmar adjuntando su propia evidencia', function () {
    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $this->comision->order_id,
        'customer_id' => $this->customerId,
        'declared_delivered_at' => now()->subDay(),
    ]);

    app(ConfirmOrderDeliveryUseCase::class)->execute(
        $this->comision->order_id,
        $this->customerId,
        [['url' => '/storage/delivery-evidence/x.jpg', 'type' => 'image']]
    );

    $expediente = OrderDeliveryConfirmation::where('order_id', $this->comision->order_id)->first();

    expect($expediente->confirmation_evidence)->toHaveCount(1)
        ->and($expediente->released_by)->toBe('customer');
});

it('confirmar sin evidencia sigue siendo válido', function () {
    // Exigirle una foto al comprador convertiria el tramite en un obstaculo, y un comprador
    // que no confirma libera por plazo igualmente. La evidencia se pide, no se impone.
    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => $this->tenant->id,
        'order_id' => $this->comision->order_id,
        'customer_id' => $this->customerId,
        'declared_delivered_at' => now()->subDay(),
    ]);

    app(ConfirmOrderDeliveryUseCase::class)->execute($this->comision->order_id, $this->customerId);

    expect($this->comision->fresh()->released_at)->not->toBeNull();
});

it('el expediente dice si toca confirmar', function () {
    $caso = app(GetOrderDeliveryStatusUseCase::class);

    // Enviado pero no entregado: todavia no toca.
    app(AttachShipmentEvidenceUseCase::class)->execute($this->comision->order_id, [foto()]);
    expect($caso->execute($this->comision->order_id)['can_confirm'])->toBeFalse();

    // Entrega declarada: ahora si.
    OrderDeliveryConfirmation::where('order_id', $this->comision->order_id)
        ->update(['declared_delivered_at' => now(), 'customer_id' => $this->customerId]);
    expect($caso->execute($this->comision->order_id)['can_confirm'])->toBeTrue();

    // Ya confirmado: se acabo.
    app(ConfirmOrderDeliveryUseCase::class)->execute($this->comision->order_id, $this->customerId);
    expect($caso->execute($this->comision->order_id)['can_confirm'])->toBeFalse();
});

it('sin expediente no hay nada que enseñar', function () {
    expect(app(GetOrderDeliveryStatusUseCase::class)->execute((string) Str::uuid()))->toBeNull();
});

it('el expediente expone la evidencia de las dos partes', function () {
    // Las dos partes ven lo mismo, a proposito: una prueba solo sirve para zanjar una
    // discusion si las dos la tienen delante.
    app(AttachShipmentEvidenceUseCase::class)->execute($this->comision->order_id, [foto()]);
    OrderDeliveryConfirmation::where('order_id', $this->comision->order_id)
        ->update(['declared_delivered_at' => now(), 'customer_id' => $this->customerId]);

    app(ConfirmOrderDeliveryUseCase::class)->execute(
        $this->comision->order_id,
        $this->customerId,
        [['url' => '/storage/delivery-evidence/recibido.jpg', 'type' => 'image']]
    );

    $estado = app(GetOrderDeliveryStatusUseCase::class)->execute($this->comision->order_id);

    expect($estado['shipment_evidence'])->toHaveCount(1)
        ->and($estado['confirmation_evidence'])->toHaveCount(1)
        ->and($estado['released_by'])->toBe('customer');
});
