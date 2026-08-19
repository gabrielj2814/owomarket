<?php

declare(strict_types=1);

namespace Src\Coupon\Application\DTOs;

use Src\Coupon\Domain\Entities\Coupon;

final class ValidateCouponResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly float $discountAmount,
        public readonly float $finalTotal,
        public readonly string $message,
        public readonly ?Coupon $coupon = null
    ) {}

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'discount_amount' => $this->discountAmount,
            'final_total' => $this->finalTotal,
            'message' => $this->message,
            'coupon' => $this->coupon?->toArray(),
        ];
    }
}
