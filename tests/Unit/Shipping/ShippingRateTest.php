<?php

declare(strict_types=1);

use Src\Shipping\Domain\Entities\ShippingRate;
use Src\Shipping\Domain\ValueObjects\ShippingRateCost;
use Src\Shipping\Domain\ValueObjects\ShippingRateName;
use Src\Shipping\Domain\ValueObjects\ShippingRateType;
use Src\Shipping\Domain\ValueObjects\ShippingZoneId;

/**
 * Hallazgo D5.
 *
 * `appliesTo()` devolvía `true` para las tarifas gratuitas **antes** de evaluar
 * `minValue`/`maxValue`, así que un «Envío gratis a partir de $100» se aplicaba a un
 * pedido de $5; y como era la opción más barata, `CalculateShippingOptionsUseCase` la
 * marcaba como recomendada: todos los envíos salían gratis.
 *
 * Y `calculateCost()` no recibía ni el peso ni el valor del pedido, así que una tarifa
 * «$3 por kg» cobraba $3 por un pedido de 20 kg en vez de $60.
 */
function tarifa(ShippingRateType $type, float $cost, ?float $minValue = null, ?float $maxValue = null): ShippingRate
{
    return ShippingRate::create(
        shippingZoneId: ShippingZoneId::fromString('11111111-1111-4111-8111-111111111111'),
        name: ShippingRateName::make('Tarifa de prueba'),
        type: $type,
        cost: ShippingRateCost::fromFloat($cost),
        minValue: $minValue,
        maxValue: $maxValue
    );
}

test('el envío gratis respeta su umbral mínimo', function () {
    $gratis = tarifa(ShippingRateType::free(), 0.0, minValue: 100.0);

    // El escenario del hallazgo: un pedido de $5 se llevaba el envío gratis.
    expect($gratis->appliesTo(5.0))->toBeFalse();
    expect($gratis->appliesTo(150.0))->toBeTrue();
});

test('el envío gratis sigue costando cero cuando aplica', function () {
    $gratis = tarifa(ShippingRateType::free(), 0.0, minValue: 100.0);

    expect($gratis->calculateCost(150.0))->toBe(0.0);
});

test('una tarifa por peso cobra en proporción al peso', function () {
    $porPeso = tarifa(ShippingRateType::weightBased(), 3.0);

    // $3 por kg sobre 20 kg son $60, no $3.
    expect($porPeso->calculateCost(0.0, 20.0))->toBe(60.0);
    expect($porPeso->calculateCost(0.0, 0.5))->toBe(1.5);
});

test('una tarifa plana sigue cobrando su importe fijo', function () {
    $plana = tarifa(ShippingRateType::flat(), 8.0);

    expect($plana->calculateCost(500.0, 20.0))->toBe(8.0);
});

test('una tarifa por peso compara sus umbrales contra el peso, no contra el importe', function () {
    $porPeso = tarifa(ShippingRateType::weightBased(), 3.0, minValue: 5.0);

    expect($porPeso->appliesTo(1000.0, 2.0))->toBeFalse();
    expect($porPeso->appliesTo(10.0, 8.0))->toBeTrue();
});
