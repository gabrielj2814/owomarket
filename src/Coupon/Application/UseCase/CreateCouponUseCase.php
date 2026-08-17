<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use InvalidArgumentException;
use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponStatus;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;

final class CreateCouponUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository
    ) {}

    public function execute(
        string $code,
        string $type,
        float $value,
        string $validFrom,
        string $validTo,
        ?float $minOrderAmount = null,
        ?int $usageLimit = null,
        ?int $usageLimitPerCustomer = null,
        bool $isActive = true,
        ?array $applicableCategories = null,
        ?array $applicableProducts = null
    ): Coupon {
        $couponCode = CouponCode::fromString($code);
        $couponType = CouponType::fromString($type);
        $couponValue = CouponValue::create($value, $couponType);
        $dateRange = CouponDateRange::create($validFrom, $validTo);

        $existing = $this->repository->findByCode($couponCode);
        if ($existing !== null) {
            throw new InvalidArgumentException(
                sprintf('Ya existe un cupón con el código "%s".', $couponCode->value())
            );
        }

        $coupon = Coupon::create(
            code: $couponCode,
            type: $couponType,
            value: $couponValue,
            dateRange: $dateRange,
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat($minOrderAmount),
            usageLimit: CouponUsageLimit::fromNullableInt($usageLimit),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt($usageLimitPerCustomer),
            isActive: CouponStatus::fromBool($isActive),
            applicableCategories: $applicableCategories,
            applicableProducts: $applicableProducts
        );

        return $this->repository->save($coupon);
    }
}
