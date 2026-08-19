<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\ExchangeRate\Domain\ValueObjects\RateAmount;
use Src\ExchangeRate\Domain\ValueObjects\RateDate;
use Src\ExchangeRate\Domain\ValueObjects\RateSource;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'id' => (string) Str::uuid(),
        'name' => 'Super Admin',
        'email' => 'admin@owomarket.com',
    ]);
});

test('POST /admin/backoffice/exchange-rates/sync-bcv triggers scraper and updates active rate', function () {
    $htmlFixture = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <div id="dolar" class="col-sm-12 col-xs-12 ">        
        <div class="field-content">
            <div class="row recuadrotsmc">
                <div class="col-sm-6 col-xs-6">
                    <img src="/sites/default/files/dollar-04_2.png" class="icono_bss_blanco1"> 		
                    <span> USD</span>
                </div>
                <div class="col-sm-6 col-xs-6 centrado textp">
                    <strong class="strong-tb">775,33560000</strong>
                </div>
            </div>  
        </div>
    </div>
    <div class="pull-right dinpro"><span> Fecha Valor: </span> <span> Miércoles, 19 Agosto 2026</span></div>
</body>
</html>
HTML;

    Http::fake([
        'https://www.bcv.org.ve/*' => Http::response($htmlFixture, 200),
    ]);

    $response = $this->actingAs($this->admin)->postJson('/admin/backoffice/exchange-rates/sync-bcv');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'base_currency' => 'USD',
                'target_currency' => 'VES',
                'rate' => 775.3356,
                'source' => 'BCV_SCRAPING',
                'is_active' => true,
            ],
        ]);
});

test('POST /admin/backoffice/exchange-rates/manual registers a contingency manual rate', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/backoffice/exchange-rates/manual', [
        'rate' => 810.50,
        'rate_date' => '2026-08-19',
        'note' => 'Ajuste manual de contingencia por mantenimiento en portal BCV',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'rate' => 810.5,
                'source' => 'MANUAL_ADMIN',
                'is_active' => true,
            ],
        ]);

    $repository = app(ExchangeRateRepositoryInterface::class);
    $active = $repository->findActive(CurrencyCode::usd(), CurrencyCode::ves());

    expect($active)->not->toBeNull();
    expect($active->getRate()->value())->toBe(810.5);
    expect($active->getSource()->value())->toBe('MANUAL_ADMIN');
});

test('GET /admin/backoffice/exchange-rates/history returns paginated rate history', function () {
    $generator = new LaravelUuidGenerator;
    $repository = app(ExchangeRateRepositoryInterface::class);

    for ($i = 1; $i <= 3; $i++) {
        $rate = ExchangeRate::create(
            $generator,
            CurrencyCode::usd(),
            CurrencyCode::ves(),
            RateAmount::make(700.0 + $i),
            RateSource::bcv(),
            RateDate::make("2026-08-0{$i}"),
            $i === 3
        );
        $repository->save($rate);
    }

    $response = $this->actingAs($this->admin)->getJson('/admin/backoffice/exchange-rates/history?page=1&per_page=10');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'current_page', 'per_page', 'last_page'],
        ]);
});
