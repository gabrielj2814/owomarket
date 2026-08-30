<?php

declare(strict_types=1);

use Src\Payment\Domain\ValueObjects\PaymentMethod;

/**
 * Hallazgo PY1: este fichero probaba además `PaymentId`, `PaymentStatus` y dos pasarelas.
 * Los cuatro se borraron con la capa de pasarelas, que no participaba en ningún cobro.
 *
 * `PaymentMethod` sí sigue vivo: lo usan los dos proveedores de métodos de pago y los
 * controladores de checkout.
 */
it('creates and validates PaymentMethod correctly', function () {
    $method = PaymentMethod::fromString('MANUAL_TRANSFER');

    expect($method->value())->toBe('manual_transfer')
        ->and($method->isManual())->toBeTrue();
});
