<?php

declare(strict_types=1);

use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Application\UseCase\ConsultCouponByIdUseCase;
use Src\Coupon\Application\UseCase\CreateCouponUseCase;
use Src\Coupon\Application\UseCase\DeleteCouponUseCase;
use Src\Coupon\Application\UseCase\EditCouponUseCase;
use Src\Coupon\Application\UseCase\ValidateCouponUseCase;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\Exceptions\CouponNotFoundException;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponId;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponStatus;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;

describe('Coupon Use Cases Unit Tests', function () {
    test('CreateCouponUseCase creates a new coupon when code is unique', function () {
        $repository = Mockery::mock(CouponRepositoryInterface::class);

        $repository->shouldReceive('findByCode')
            ->once()
            ->andReturnNull();

        $savedCoupon = new Coupon(
            id: CouponId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            code: CouponCode::fromString('WELCOME10'),
            type: CouponType::percentage(),
            value: CouponValue::create(10, CouponType::percentage()),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(20.0),
            usageLimit: CouponUsageLimit::fromNullableInt(100),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt(1),
            usedCount: 0,
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
            isActive: CouponStatus::active()
        );

        $repository->shouldReceive('save')
            ->once()
            ->andReturn($savedCoupon);

        $useCase = new CreateCouponUseCase($repository);
        $result = $useCase->execute(
            code: 'welcome10',
            type: 'percentage',
            value: 10,
            validFrom: '2026-01-01',
            validTo: '2026-12-31',
            minOrderAmount: 20.0,
            usageLimit: 100,
            usageLimitPerCustomer: 1
        );

        expect($result->code()->value())->toBe('WELCOME10')
            ->and($result->type()->value())->toBe('percentage')
            ->and($result->value()->value())->toBe(10.0);
    });

    test('CreateCouponUseCase throws exception when code already exists', function () {
        $repository = Mockery::mock(CouponRepositoryInterface::class);

        $existing = new Coupon(
            id: CouponId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            code: CouponCode::fromString('WELCOME10'),
            type: CouponType::percentage(),
            value: CouponValue::create(10, CouponType::percentage()),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(null),
            usageLimit: CouponUsageLimit::fromNullableInt(null),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt(null),
            usedCount: 0,
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
            isActive: CouponStatus::active()
        );

        $repository->shouldReceive('findByCode')
            ->once()
            ->andReturn($existing);

        $useCase = new CreateCouponUseCase($repository);

        expect(fn () => $useCase->execute(
            code: 'welcome10',
            type: 'percentage',
            value: 10,
            validFrom: '2026-01-01',
            validTo: '2026-12-31'
        ))->toThrow(InvalidArgumentException::class);
    });

    test('EditCouponUseCase updates coupon details', function () {
        $repository = Mockery::mock(CouponRepositoryInterface::class);

        $existing = new Coupon(
            id: CouponId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            code: CouponCode::fromString('OLDCODE'),
            type: CouponType::percentage(),
            value: CouponValue::create(10, CouponType::percentage()),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(null),
            usageLimit: CouponUsageLimit::fromNullableInt(null),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt(null),
            usedCount: 0,
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
            isActive: CouponStatus::active()
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existing);

        $repository->shouldReceive('findByCode')
            ->once()
            ->andReturnNull();

        $repository->shouldReceive('update')
            ->once()
            ->andReturnUsing(fn (Coupon $c) => $c);

        $useCase = new EditCouponUseCase($repository);
        $result = $useCase->execute(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            code: 'NEWCODE',
            type: 'fixed_amount',
            value: 30.0,
            validFrom: '2026-02-01',
            validTo: '2026-11-30',
            minOrderAmount: 100.0,
            isActive: true
        );

        expect($result->code()->value())->toBe('NEWCODE')
            ->and($result->type()->value())->toBe('fixed_amount')
            ->and($result->value()->value())->toBe(30.0);
    });

    test('ConsultCouponByIdUseCase throws CouponNotFoundException when not found', function () {
        $repository = Mockery::mock(CouponRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->andReturnNull();

        $useCase = new ConsultCouponByIdUseCase($repository);

        expect(fn () => $useCase->execute('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a99'))
            ->toThrow(CouponNotFoundException::class);
    });

    test('DeleteCouponUseCase deletes existing coupon', function () {
        $repository = Mockery::mock(CouponRepositoryInterface::class);

        $existing = new Coupon(
            id: CouponId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            code: CouponCode::fromString('PROMO'),
            type: CouponType::percentage(),
            value: CouponValue::create(15, CouponType::percentage()),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(null),
            usageLimit: CouponUsageLimit::fromNullableInt(null),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt(null),
            usedCount: 0,
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
            isActive: CouponStatus::active()
        );

        $repository->shouldReceive('findById')
            ->once()
            ->andReturn($existing);

        $repository->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(fn (CouponId $id) => $id->value() === 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'));

        $useCase = new DeleteCouponUseCase($repository);
        $useCase->execute('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11');
        expect(true)->toBeTrue();
    });

    test('ValidateCouponUseCase applies discount accurately when valid', function () {
        $repository = Mockery::mock(CouponRepositoryInterface::class);

        $coupon = new Coupon(
            id: CouponId::fromString('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'),
            code: CouponCode::fromString('FLASH20'),
            type: CouponType::percentage(),
            value: CouponValue::create(20, CouponType::percentage()),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(50.0),
            usageLimit: CouponUsageLimit::fromNullableInt(10),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt(1),
            usedCount: 0,
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
            isActive: CouponStatus::active()
        );

        $repository->shouldReceive('findByCode')
            ->once()
            ->andReturn($coupon);

        $useCase = new ValidateCouponUseCase($repository);
        $result = $useCase->execute('flash20', 100.0, '2026-06-15');

        expect($result->isValid)->toBeTrue()
            ->and($result->discountAmount)->toBe(20.0)
            ->and($result->finalTotal)->toBe(80.0);
    });
});
