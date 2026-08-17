<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Src\Coupon\Application\DTOs\CouponFilterCriteria;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponStatus;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;
use Src\Coupon\Infrastructure\Eloquent\Repositories\CouponRepository;

beforeEach(function () {
    $migration = require base_path('database/migrations/tenant/2025_10_28_144655_create_coupons.php');
    if (! Schema::hasTable('coupons')) {
        $migration->up();
    }

    $this->repository = new CouponRepository;
});

test('CouponRepository creates and retrieves coupon by id and code', function () {
    $coupon = Coupon::create(
        code: CouponCode::fromString('OFERTA15'),
        type: CouponType::percentage(),
        value: CouponValue::create(15, CouponType::percentage()),
        dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
        minOrderAmount: CouponMinOrderAmount::fromNullableFloat(30.0),
        usageLimit: CouponUsageLimit::fromNullableInt(50),
        isActive: CouponStatus::active()
    );

    $saved = $this->repository->save($coupon);

    expect($saved->id())->not->toBeNull()
        ->and($saved->code()->value())->toBe('OFERTA15')
        ->and($saved->value()->value())->toBe(15.0);

    $foundById = $this->repository->findById($saved->id());
    expect($foundById)->not->toBeNull()
        ->and($foundById->code()->value())->toBe('OFERTA15');

    $foundByCode = $this->repository->findByCode(CouponCode::fromString('OFERTA15'));
    expect($foundByCode)->not->toBeNull()
        ->and($foundByCode->id()->value())->toBe($saved->id()->value());
});

test('CouponRepository edits existing coupon', function () {
    $coupon = $this->repository->save(Coupon::create(
        code: CouponCode::fromString('INICIAL'),
        type: CouponType::fixedAmount(),
        value: CouponValue::create(20, CouponType::fixedAmount()),
        dateRange: CouponDateRange::create('2026-01-01', '2026-12-31')
    ));

    $coupon->changeCode(CouponCode::fromString('ACTUALIZADO'));
    $coupon->changeTypeAndValue(CouponType::percentage(), CouponValue::create(50, CouponType::percentage()));
    $coupon->deactivate();

    $updated = $this->repository->update($coupon);

    expect($updated->code()->value())->toBe('ACTUALIZADO')
        ->and($updated->type()->value())->toBe('percentage')
        ->and($updated->value()->value())->toBe(50.0)
        ->and($updated->isActive()->value())->toBeFalse();
});

test('CouponRepository deletes coupon', function () {
    $coupon = $this->repository->save(Coupon::create(
        code: CouponCode::fromString('BORRAR'),
        type: CouponType::percentage(),
        value: CouponValue::create(10, CouponType::percentage()),
        dateRange: CouponDateRange::create('2026-01-01', '2026-12-31')
    ));

    $this->repository->delete($coupon->id());

    $found = $this->repository->findById($coupon->id());
    expect($found)->toBeNull();
});

test('CouponRepository filters coupons by search, type, active status and valid date', function () {
    $this->repository->save(Coupon::create(
        code: CouponCode::fromString('SUMMER_PCT'),
        type: CouponType::percentage(),
        value: CouponValue::create(20, CouponType::percentage()),
        dateRange: CouponDateRange::create('2026-06-01', '2026-08-31'),
        isActive: CouponStatus::active()
    ));

    $this->repository->save(Coupon::create(
        code: CouponCode::fromString('WINTER_FIXED'),
        type: CouponType::fixedAmount(),
        value: CouponValue::create(10, CouponType::fixedAmount()),
        dateRange: CouponDateRange::create('2026-12-01', '2026-12-31'),
        isActive: CouponStatus::inactive()
    ));

    $searchFilter = $this->repository->filter(new CouponFilterCriteria(
        search: 'SUMMER'
    ));
    expect($searchFilter->total)->toBe(1)
        ->and($searchFilter->items[0]->code()->value())->toBe('SUMMER_PCT');

    $activeFilter = $this->repository->filter(new CouponFilterCriteria(
        isActive: true
    ));
    expect($activeFilter->total)->toBe(1)
        ->and($activeFilter->items[0]->code()->value())->toBe('SUMMER_PCT');

    $dateFilter = $this->repository->filter(new CouponFilterCriteria(
        validDate: '2026-07-15'
    ));
    expect($dateFilter->total)->toBe(1)
        ->and($dateFilter->items[0]->code()->value())->toBe('SUMMER_PCT');
});
