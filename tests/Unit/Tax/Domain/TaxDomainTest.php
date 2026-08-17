<?php

declare(strict_types=1);

use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;

describe('Tax Domain Unit Tests', function () {
    test('TaxRateName validates min length', function () {
        $name = TaxRateName::make('IVA 16%');
        expect($name->value())->toBe('IVA 16%');

        expect(fn () => TaxRateName::make('A'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('TaxRatePercentage validates between 0 and 100', function () {
        $rate = TaxRatePercentage::create(16.0);
        expect($rate->value())->toBe(16.0);

        expect(fn () => TaxRatePercentage::create(150))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => TaxRatePercentage::create(-2))
            ->toThrow(InvalidArgumentException::class);
    });

    test('TaxRate entity calculates tax amount correctly and respects active status', function () {
        $tax = TaxRate::create(
            name: TaxRateName::make('IVA General'),
            rate: TaxRatePercentage::create(16.0),
            country: 'MX'
        );

        expect($tax->calculateTax(100.0))->toBe(16.0)
            ->and($tax->calculateTax(250.0))->toBe(40.0);

        $tax->deactivate();
        expect($tax->calculateTax(100.0))->toBe(0.0);
    });
});
