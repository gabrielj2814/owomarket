<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;

test('artisan exchange-rate:sync-bcv command scrapes and updates official rate', function () {
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

    $this->artisan('exchange-rate:sync-bcv')
        ->expectsOutputToContain('Iniciando sincronización de tasa de cambio oficial del BCV...')
        ->expectsOutputToContain('Tasa BCV actualizada exitosamente')
        ->assertSuccessful();

    $repository = app(ExchangeRateRepositoryInterface::class);
    $active = $repository->findActive(CurrencyCode::usd(), CurrencyCode::ves());

    expect($active)->not->toBeNull();
    expect($active->getRate()->value())->toBe(775.3356);
    expect($active->getSource()->value())->toBe('BCV_SCRAPING');
});
