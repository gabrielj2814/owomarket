<?php

declare(strict_types=1);

use Src\ExchangeRate\Infrastructure\Scrapers\BcvWebScraper;

test('BcvWebScraper correctly parses official BCV HTML snippet', function () {
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

    $scraper = new BcvWebScraper;
    $result = $scraper->parseHtml($htmlFixture);

    expect($result['success'])->toBeTrue();
    expect($result['rate'])->toBe(775.3356);
    expect($result['rate_date'])->toBe('2026-08-19');
    expect($result['error_message'])->toBeNull();
});

test('BcvWebScraper handles missing or invalid HTML gracefully', function () {
    $invalidHtml = '<div>No dollar rate here</div>';

    $scraper = new BcvWebScraper;
    $result = $scraper->parseHtml($invalidHtml);

    expect($result['success'])->toBeFalse();
    expect($result['rate'])->toBe(0.0);
    expect($result['error_message'])->not->toBeNull();
});
