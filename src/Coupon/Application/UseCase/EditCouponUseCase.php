<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use InvalidArgumentException;
use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\Exceptions\CouponNotFoundException;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponId;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;

final class EditCouponUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository
    ) {}

    public function execute(
        string $id,
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
        $couponId = CouponId::fromString($id);
        $coupon = $this->repository->findById($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException($id);
        }

        $couponCode = CouponCode::fromString($code);
        $couponType = CouponType::fromString($type);
        $couponValue = CouponValue::create($value, $couponType);
        $dateRange = CouponDateRange::create($validFrom, $validTo);

        $existingWithCode = $this->repository->findByCode($couponCode);
        if ($existingWithCode !== null && $existingWithCode->id()?->value() !== $couponId->value()) {
            throw new InvalidArgumentException(
                sprintf('El código "%s" ya está en uso por otro cupón.', $couponCode->value())
            );
        }

        $coupon->changeCode($couponCode);
        $coupon->changeTypeAndValue($couponType, $couponValue);
        $coupon->changeDateRange($dateRange);
        $coupon->changeMinOrderAmount(CouponMinOrderAmount::fromNullableFloat($minOrderAmount));
        $coupon->changeUsageLimit(CouponUsageLimit::fromNullableInt($usageLimit));
        $coupon->changeUsageLimitPerCustomer(CouponUsageLimit::fromNullableInt($usageLimitPerCustomer));
        $coupon->changeApplicableCategories($applicableCategories);
        $coupon->changeApplicableProducts($applicableProducts);

        if ($isActive) {
            $coupon->activate();
        } else {
            $coupon->deactivate();
        }

        return $this->repository->update($coupon);
    }
}
