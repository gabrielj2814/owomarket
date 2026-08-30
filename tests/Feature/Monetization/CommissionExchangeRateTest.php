<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Monetization\Application\UseCases\CalculateAndRecordOrderCommissionUseCase;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

/**
 * Fase 1 de `planes/por_hacer/PLAN_WALLET_Y_RETIROS.md`.
 *
 * La wallet guarda el saldo de cada tienda en bolívares congelados a la tasa de la venta, de
 * modo que la plataforma le deba exactamente los bolívares que recibió del comprador. Eso
 * obliga a capturar la tasa en el momento de vender: es el único dato del plan que no se
 * puede recuperar después, porque no existe en ninguna otra parte.
 */
beforeEach(function () {
    $this->tenant = Tenant::create([
        'id' => 'shop-'.Str::random(6),
        'name' => 'Tienda Wallet',
        'slug' => 'wallet-'.Str::random(4),
        'status' => 'active',
        'request' => 'approved',
    ]);
});

function tasaActivaDe(float $valor, ?string $fecha = null): void
{
    // `findActive` ordena por `rate_date` y luego por `created_at`. Dos tasas del mismo día
    // creadas en el mismo segundo empatan y el desempate queda al azar, así que cada tasa de
    // este fichero lleva su fecha — que además es el escenario real: la tasa cambia de un día
    // para otro, no dos veces en el mismo segundo.
    app(ExchangeRateRepositoryInterface::class)->save(
        ExchangeRate::create(
            new LaravelUuidGenerator,
            CurrencyCode::usd(),
            CurrencyCode::ves(),
            RateAmount::make($valor),
            RateSource::bcv(),
            $fecha !== null ? RateDate::make($fecha) : RateDate::today()
        )
    );
}

function registrarComision(Tenant $tenant, float $total = 100.0): Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission
{
    return app(CalculateAndRecordOrderCommissionUseCase::class)->execute(
        tenantId: $tenant->id,
        orderId: (string) Str::uuid(),
        orderNumber: 'ORD-'.strtoupper(Str::random(6)),
        orderTotal: $total,
        paymentGateway: 'pago_movil',
        currency: 'USD'
    );
}

it('congela la tasa de cambio en el momento de la venta', function () {
    tasaActivaDe(775.3356);

    $comision = registrarComision($this->tenant);

    expect($comision->exchange_rate)->toBe(775.3356);
});

it('una venta no se cae porque no haya tasa activa', function () {
    // Sin tasa, `GetActiveExchangeRateUseCase` lanza. Si eso tumbara el registro de la
    // comisión, un fallo de sincronización del BCV dejaría ventas sin comisión — que es
    // peor que una comisión sin valorar.
    $comision = registrarComision($this->tenant);

    expect($comision->exchange_rate)->toBeNull()
        ->and($comision->status)->toBe('awaiting_payment');
});

it('el saldo en bolívares sale derivado, sin columnas de importes', function () {
    // Es la consulta con la que la Fase 2 calculará la wallet. Se comprueba aquí para que la
    // columna quede fijada como suficiente: si algún día hicieran falta importes en Bs
    // guardados, este test es el que lo delata.
    // Ayer la tasa era 100 y se vendieron 200.
    tasaActivaDe(100.0, now()->subDay()->format('Y-m-d'));
    registrarComision($this->tenant, 200.0);   // 200 - 16 de comisión = 184 USD → 18.400 Bs

    // Hoy es 50 y se venden 100. La venta de ayer NO se revaloriza.
    tasaActivaDe(50.0);
    registrarComision($this->tenant, 100.0);   // 100 - 8  de comisión =  92 USD →  4.600 Bs

    $saldoBs = (float) DB::table('platform_commissions')
        ->where('tenant_id', $this->tenant->id)
        ->whereNotNull('exchange_rate')
        ->sum(DB::raw('(order_total - commission_amount) * exchange_rate'));

    // Cada venta conserva la tasa de SU día: 18.400 + 4.600. Si la valoración se hiciera con
    // la tasa vigente al consultar, las dos usarían 50 y saldrían 13.800: la plataforma le
    // debería a la tienda mucho menos de los bolívares que de verdad recibió.
    expect($saldoBs)->toBe(23000.0);
});
