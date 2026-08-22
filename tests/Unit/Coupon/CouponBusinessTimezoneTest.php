<?php

declare(strict_types=1);

use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\Exceptions\CouponExpiredException;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponValue;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;

/*
|--------------------------------------------------------------------------
| Hallazgo Auditoria #4 — el dia de calendario es el del negocio, no el de UTC
|--------------------------------------------------------------------------
|
| `Coupon::validateUsability()` resolvia el dia con `date()`, que usa la zona por defecto
| de PHP (UTC). Caracas esta cuatro horas por detras, asi que el dia cambiaba a las 20:00
| hora local: un cupon valido «hasta el 21» dejaba de funcionar esa misma tarde, cuatro
| horas antes de lo prometido al cliente.
|
| El arreglo NO mueve `app.timezone`. Lo almacenado sigue en UTC —moverlo cambiaria el
| significado de todas las fechas ya guardadas— y solo se pregunta la zona del negocio
| donde se decide un dia de calendario.
*/

uses(Tests\TestCase::class);

/** Cupon del 20 al 21, sin limite de uso ni minimo de compra. */
function cuponHastaEl21(): Coupon
{
    return Coupon::create(
        code: CouponCode::fromString('VERANO21'),
        type: CouponType::fixedAmount(),
        value: CouponValue::create(10.0, CouponType::fixedAmount()),
        dateRange: CouponDateRange::create('2026-08-20', '2026-08-21')
    );
}

test('un cupon valido hasta el 21 sigue funcionando a las 23:30 de Caracas', function () {
    // 23:30 en Caracas son las 03:30 UTC del dia 22. Antes del arreglo, el cupon ya
    // estaba «caducado» a esa hora aunque en Venezuela fuera todavia el dia 21.
    expect(fn () => cuponHastaEl21()->validateUsability(100.0, '2026-08-22T03:30:00+00:00'))
        ->not->toThrow(CouponExpiredException::class);
});

test('el mismo cupon caduca pasada la medianoche de Caracas', function () {
    // 00:05 del 22 en Caracas son las 04:05 UTC. Ahi si toca rechazarlo.
    expect(fn () => cuponHastaEl21()->validateUsability(100.0, '2026-08-22T04:05:00+00:00'))
        ->toThrow(CouponExpiredException::class);
});

test('a mediodia del 21 el cupon vale, mires desde donde mires', function () {
    // Control: una hora en la que las dos zonas coinciden en el dia. Si este fallara, el
    // problema no seria de zona horaria.
    expect(fn () => cuponHastaEl21()->validateUsability(100.0, '2026-08-21T16:00:00+00:00'))
        ->not->toThrow(CouponExpiredException::class);
});

test('la fecha valor de la tasa BCV tambien usa el dia del negocio', function () {
    // Mismo fallo, otro sitio: `RateDate::today()` usaba `new DateTimeImmutable` a secas.
    // Entre las 20:00 y la medianoche de Caracas devolvia ya el dia siguiente, con lo que
    // una tasa sincronizada a las 21:00 quedaba fechada manana.
    $esperado = (new DateTimeImmutable('now', new DateTimeZone(config('app.business_timezone'))))->format('Y-m-d');

    expect(RateDate::today()->value())->toBe($esperado);
});

test('el almacenamiento sigue en UTC', function () {
    // La garantia que hace seguro este arreglo: no se toca donde se guarda, asi que
    // ninguna fecha ya escrita cambia de significado.
    expect(config('app.timezone'))->toBe('UTC');
});
