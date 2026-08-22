<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Payment\Infrastructure\Eloquent\Models\CentralSetting;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

/**
 * La Fase 0.5 (hallazgo G1) sacó los datos de cobro de demostración del checkout **del
 * inquilino**, pero el checkout **central** se quedó con los suyos incrustados en el TSX:
 * Banesco (0134), J-501234567, 0412-9998877. En un pedido multi-tienda cobra la
 * plataforma, así que el comprador transfería a una cuenta que no era de nadie.
 */
function seedCentralPaymentSetting(string $key, string $value): void
{
    CentralSetting::create([
        'id' => (string) Str::uuid(),
        'key' => $key,
        'value' => $value,
        'type' => 'string',
        'group' => 'payment',
    ]);
}

test('sin datos de cobro configurados el checkout central no ofrece ningún método', function () {
    $this->get('http://owomarket.local/checkout')
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));
});

test('el checkout central no expone nunca los datos de demostración hardcodeados', function () {
    $response = $this->get('http://owomarket.local/checkout');

    $response->assertStatus(200);
    $response->assertDontSee('J-501234567');
    $response->assertDontSee('0412-9998877');
});

test('Pago Móvil central sólo se ofrece con banco, RIF y teléfono configurados', function () {
    seedCentralPaymentSetting('central_pago_movil_bank_name', '0105 - Banco Mercantil');
    seedCentralPaymentSetting('central_pago_movil_document_id', 'J-50999888-1');

    // Configuración incompleta: falta el teléfono.
    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));

    seedCentralPaymentSetting('central_pago_movil_phone', '0424-5556677');

    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.id', 'pago_movil')
            ->where('payment_methods.0.bank_name', '0105 - Banco Mercantil')
            ->where('payment_methods.0.document_id', 'J-50999888-1')
            ->where('payment_methods.0.phone', '0424-5556677')
        );
});

test('la tasa que acompaña a Pago Móvil es la real, no una inventada', function () {
    seedCentralPaymentSetting('central_pago_movil_bank_name', '0105 - Banco Mercantil');
    seedCentralPaymentSetting('central_pago_movil_document_id', 'J-50999888-1');
    seedCentralPaymentSetting('central_pago_movil_phone', '0424-5556677');

    app(ExchangeRateRepositoryInterface::class)->save(ExchangeRate::create(
        new LaravelUuidGenerator,
        CurrencyCode::usd(),
        CurrencyCode::ves(),
        RateAmount::make(812.5),
        RateSource::bcv(),
        RateDate::today()
    ));

    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page
            ->where('payment_methods.0.exchange_rate_ves', 812.5)
        );
});

test('Binance Pay central sólo se ofrece con un Pay ID propio configurado', function () {
    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page->has('payment_methods', 0));

    seedCentralPaymentSetting('central_binance_pay_id', '987654321');

    $this->get('http://owomarket.local/checkout')
        ->assertInertia(fn (Assert $page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.id', 'binance_pay')
            ->where('payment_methods.0.binance_pay_id', '987654321')
        );
});
