<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use Exception;
use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Application\DTOs\ValidateCouponResult;
use Src\Coupon\Domain\ValueObjects\CouponCode;

final class ValidateCouponUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository
    ) {}

    public function execute(string $code, float $orderSubtotal, string $currentDate = 'now'): ValidateCouponResult
    {
        $couponCode = CouponCode::fromString($code);
        $coupon = $this->repository->findByCode($couponCode);

        if ($coupon === null) {
            return new ValidateCouponResult(
                isValid: false,
                discountAmount: 0.0,
                finalTotal: $orderSubtotal,
                message: sprintf('El cupón "%s" no existe.', $code)
            );
        }

        try {
            $coupon->validateUsability($orderSubtotal, $currentDate);
            $discount = $coupon->calculateDiscount($orderSubtotal);
            $finalTotal = round(max(0.0, $orderSubtotal - $discount), 2);

            return new ValidateCouponResult(
                isValid: true,
                discountAmount: $discount,
                finalTotal: $finalTotal,
                message: 'Cupón aplicado exitosamente.',
                coupon: $coupon
            );
        } catch (Exception $e) {
            return new ValidateCouponResult(
                isValid: false,
                discountAmount: 0.0,
                finalTotal: $orderSubtotal,
                message: $e->getMessage(),
                coupon: $coupon
            );
        }
    }
}
