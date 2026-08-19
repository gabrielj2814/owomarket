<?php

declare(strict_types=1);

use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\Exceptions\CouponExpiredException;
use Src\Coupon\Domain\Exceptions\CouponUsageLimitReachedException;
use Src\Coupon\Domain\Exceptions\InvalidCouponException;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;

describe('Coupon Domain Unit Tests', function () {
    test('CouponCode normalizes uppercase and validates format', function () {
        $code = CouponCode::fromString('promo-2026');
        expect($code->value())->toBe('PROMO-2026');

        expect(fn () => CouponCode::fromString('A'))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => CouponCode::fromString('INVALID!@#'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('CouponType validates percentage and fixed amount', function () {
        $pct = CouponType::percentage();
        expect($pct->isPercentage())->toBeTrue()
            ->and($pct->isFixedAmount())->toBeFalse();

        $fixed = CouponType::fixedAmount();
        expect($fixed->isFixedAmount())->toBeTrue();

        expect(fn () => CouponType::fromString('invalid_type'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('CouponValue enforces max 100 for percentage and positive values', function () {
        $val = CouponValue::create(25.5, CouponType::percentage());
        expect($val->value())->toBe(25.5);

        expect(fn () => CouponValue::create(105, CouponType::percentage()))
            ->toThrow(InvalidArgumentException::class);

        expect(fn () => CouponValue::create(-5, CouponType::fixedAmount()))
            ->toThrow(InvalidArgumentException::class);
    });

    test('CouponDateRange validates correct start and end ordering', function () {
        $range = CouponDateRange::create('2026-01-01', '2026-12-31');
        expect($range->validFrom())->toBe('2026-01-01')
            ->and($range->validTo())->toBe('2026-12-31')
            ->and($range->isDateWithin('2026-06-15'))->toBeTrue()
            ->and($range->isDateWithin('2027-01-01'))->toBeFalse();

        expect(fn () => CouponDateRange::create('2026-12-31', '2026-01-01'))
            ->toThrow(InvalidArgumentException::class);
    });

    test('Coupon entity calculates percentage and fixed discounts correctly', function () {
        $pctCoupon = Coupon::create(
            code: CouponCode::fromString('DESC10'),
            type: CouponType::percentage(),
            value: CouponValue::create(10, CouponType::percentage()),
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31'),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(50.0)
        );

        expect($pctCoupon->calculateDiscount(100.0))->toBe(10.0)
            ->and($pctCoupon->calculateDiscount(250.0))->toBe(25.0);

        $fixedCoupon = Coupon::create(
            code: CouponCode::fromString('MENOS50'),
            type: CouponType::fixedAmount(),
            value: CouponValue::create(50, CouponType::fixedAmount()),
            dateRange: CouponDateRange::create('2026-01-01', '2026-12-31')
        );

        expect($fixedCoupon->calculateDiscount(200.0))->toBe(50.0)
            ->and($fixedCoupon->calculateDiscount(30.0))->toBe(30.0); // discount capped at subtotal
    });

    test('Coupon entity validates usability, expiration, min order amount and usage limit', function () {
        $coupon = Coupon::create(
            code: CouponCode::fromString('VIP2026'),
            type: CouponType::percentage(),
            value: CouponValue::create(20, CouponType::percentage()),
            dateRange: CouponDateRange::create('2026-06-01', '2026-06-30'),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat(100.0),
            usageLimit: CouponUsageLimit::fromNullableInt(1)
        );

        // Date before range
        expect(fn () => $coupon->validateUsability(150.0, '2026-05-15'))
            ->toThrow(CouponExpiredException::class);

        // Subtotal below minOrderAmount
        expect(fn () => $coupon->validateUsability(80.0, '2026-06-15'))
            ->toThrow(InvalidCouponException::class);

        // Valid
        $coupon->validateUsability(150.0, '2026-06-15');
        $coupon->incrementUsage();

        // Limit reached
        expect(fn () => $coupon->validateUsability(150.0, '2026-06-15'))
            ->toThrow(CouponUsageLimitReachedException::class);
    });
});
