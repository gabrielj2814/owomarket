<?php

declare(strict_types=1);

use Src\Tax\Application\Contracts\TaxRateRepositoryInterface;
use Src\Tax\Application\UseCase\CalculateTaxUseCase;
use Src\Tax\Application\UseCase\ConsultTaxRateByIdUseCase;
use Src\Tax\Application\UseCase\CreateTaxRateUseCase;
use Src\Tax\Application\UseCase\EditTaxRateUseCase;
use Src\Tax\Domain\Entities\TaxRate;
use Src\Tax\Domain\Exceptions\TaxRateNotFoundException;
use Src\Tax\Domain\ValueObjects\TaxRateId;
use Src\Tax\Domain\ValueObjects\TaxRateName;
use Src\Tax\Domain\ValueObjects\TaxRatePercentage;
use Src\Tax\Domain\ValueObjects\TaxRatePriority;
use Src\Tax\Domain\ValueObjects\TaxRateStatus;

describe('Tax Use Cases Unit Tests', function () {
    test('CreateTaxRateUseCase creates a tax rate', function () {
        $repository = Mockery::mock(TaxRateRepositoryInterface::class);

        $savedTax = new TaxRate(
            id: TaxRateId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: TaxRateName::make('IVA 16%'),
            rate: TaxRatePercentage::create(16.0),
            country: 'MX',
            state: null,
            city: null,
            zip: null,
            priority: TaxRatePriority::fromInt(0),
            isActive: TaxRateStatus::active()
        );

        $repository->shouldReceive('save')
            ->once()
            ->andReturn($savedTax);

        $useCase = new CreateTaxRateUseCase($repository);
        $result = $useCase->execute(
            name: 'IVA 16%',
            rate: 16.0,
            country: 'MX'
        );

        expect($result->name()->value())->toBe('IVA 16%')
            ->and($result->rate()->value())->toBe(16.0);
    });

    test('EditTaxRateUseCase updates existing tax rate', function () {
        $repository = Mockery::mock(TaxRateRepositoryInterface::class);

        $existing = new TaxRate(
            id: TaxRateId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: TaxRateName::make('IVA 16%'),
            rate: TaxRatePercentage::create(16.0),
            country: 'MX',
            state: null,
            city: null,
            zip: null,
            priority: TaxRatePriority::fromInt(0),
            isActive: TaxRateStatus::active()
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existing);

        $repository->shouldReceive('update')
            ->once()
            ->andReturnUsing(fn (TaxRate $t) => $t);

        $useCase = new EditTaxRateUseCase($repository);
        $result = $useCase->execute(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'IVA Frontera 8%',
            rate: 8.0,
            country: 'MX',
            state: 'BC'
        );

        expect($result->name()->value())->toBe('IVA Frontera 8%')
            ->and($result->rate()->value())->toBe(8.0)
            ->and($result->state())->toBe('BC');
    });

    test('ConsultTaxRateByIdUseCase throws TaxRateNotFoundException when not found', function () {
        $repository = Mockery::mock(TaxRateRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->andReturnNull();

        $useCase = new ConsultTaxRateByIdUseCase($repository);

        expect(fn () => $useCase->execute('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a99'))
            ->toThrow(TaxRateNotFoundException::class);
    });

    test('CalculateTaxUseCase calculates tax for subtotal based on matching rates', function () {
        $repository = Mockery::mock(TaxRateRepositoryInterface::class);

        $rate1 = new TaxRate(
            id: TaxRateId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            name: TaxRateName::make('IVA 16%'),
            rate: TaxRatePercentage::create(16.0),
            country: 'MX',
            state: null,
            city: null,
            zip: null,
            priority: TaxRatePriority::fromInt(0),
            isActive: TaxRateStatus::active()
        );

        $repository->shouldReceive('findApplicableRates')
            ->once()
            ->with('MX', null, null, null)
            ->andReturn([$rate1]);

        $useCase = new CalculateTaxUseCase($repository);
        $result = $useCase->execute(
            subtotal: 100.0,
            country: 'MX'
        );

        expect($result->subtotal)->toBe(100.0)
            ->and($result->totalTax)->toBe(16.0)
            ->and($result->totalWithTax)->toBe(116.0)
            ->and(count($result->appliedRates))->toBe(1);
    });
});
