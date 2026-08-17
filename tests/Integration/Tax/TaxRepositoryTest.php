<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Src\Tax\Application\DTOs\TaxRateFilterCriteria;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;
use Src\Tax\Domain\ValueObjects\TaxRatePriority;
use Src\Tax\Domain\ValueObjects\TaxRateStatus;
use Src\Tax\Infrastructure\Eloquent\Repositories\TaxRateRepository;

beforeEach(function () {
    $migration = require base_path('database/migrations/tenant/2025_10_28_145148_create_tax_rates.php');
    if (! Schema::hasTable('tax_rates')) {
        $migration->up();
    }

    $this->repository = new TaxRateRepository;
});

test('TaxRateRepository creates and retrieves tax rate by id', function () {
    $tax = TaxRate::create(
        name: TaxRateName::make('IVA 16%'),
        rate: TaxRatePercentage::create(16.0),
        country: 'MX',
        priority: TaxRatePriority::fromInt(1),
        isActive: TaxRateStatus::active()
    );

    $saved = $this->repository->save($tax);

    expect($saved->id())->not->toBeNull()
        ->and($saved->name()->value())->toBe('IVA 16%')
        ->and($saved->rate()->value())->toBe(16.0);

    $found = $this->repository->findById($saved->id());
    expect($found)->not->toBeNull()
        ->and($found->name()->value())->toBe('IVA 16%');
});

test('TaxRateRepository edits and deletes tax rate', function () {
    $tax = $this->repository->save(TaxRate::create(
        name: TaxRateName::make('IVA 10%'),
        rate: TaxRatePercentage::create(10.0),
        country: 'CO'
    ));

    $tax->changeName(TaxRateName::make('IVA 19%'));
    $tax->changeRate(TaxRatePercentage::create(19.0));

    $updated = $this->repository->update($tax);
    expect($updated->name()->value())->toBe('IVA 19%')
        ->and($updated->rate()->value())->toBe(19.0);

    $this->repository->delete($tax->id());
    expect($this->repository->findById($tax->id()))->toBeNull();
});

test('TaxRateRepository filters and finds applicable rates', function () {
    $this->repository->save(TaxRate::create(
        name: TaxRateName::make('IVA Nacional'),
        rate: TaxRatePercentage::create(16.0),
        country: 'MX'
    ));

    $this->repository->save(TaxRate::create(
        name: TaxRateName::make('Tax US Federal'),
        rate: TaxRatePercentage::create(5.0),
        country: 'US'
    ));

    $filter = $this->repository->filter(new TaxRateFilterCriteria(country: 'MX'));
    expect($filter->total)->toBe(1)
        ->and($filter->items[0]->country())->toBe('MX');

    $applicable = $this->repository->findApplicableRates(country: 'MX');
    expect(count($applicable))->toBeGreaterThanOrEqual(1);
});
