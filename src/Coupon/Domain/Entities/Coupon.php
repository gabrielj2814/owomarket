<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\Entities;

use Src\Coupon\Domain\Exceptions\CouponExpiredException;
use Src\Coupon\Domain\Exceptions\CouponUsageLimitReachedException;
use Src\Coupon\Domain\Exceptions\InvalidCouponException;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponId;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponStatus;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;

final class Coupon
{
    public function __construct(
        private ?CouponId $id,
        private CouponCode $code,
        private CouponType $type,
        private CouponValue $value,
        private CouponMinOrderAmount $minOrderAmount,
        private CouponUsageLimit $usageLimit,
        private CouponUsageLimit $usageLimitPerCustomer,
        private int $usedCount,
        private CouponDateRange $dateRange,
        private CouponStatus $isActive,
        private ?array $applicableCategories = null,
        private ?array $applicableProducts = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public static function create(
        CouponCode $code,
        CouponType $type,
        CouponValue $value,
        CouponDateRange $dateRange,
        ?CouponMinOrderAmount $minOrderAmount = null,
        ?CouponUsageLimit $usageLimit = null,
        ?CouponUsageLimit $usageLimitPerCustomer = null,
        ?CouponStatus $isActive = null,
        ?array $applicableCategories = null,
        ?array $applicableProducts = null
    ): self {
        return new self(
            id: null,
            code: $code,
            type: $type,
            value: $value,
            minOrderAmount: $minOrderAmount ?? CouponMinOrderAmount::fromNullableFloat(null),
            usageLimit: $usageLimit ?? CouponUsageLimit::fromNullableInt(null),
            usageLimitPerCustomer: $usageLimitPerCustomer ?? CouponUsageLimit::fromNullableInt(null),
            usedCount: 0,
            dateRange: $dateRange,
            isActive: $isActive ?? CouponStatus::active(),
            applicableCategories: $applicableCategories,
            applicableProducts: $applicableProducts
        );
    }

    public function id(): ?CouponId
    {
        return $this->id;
    }

    public function code(): CouponCode
    {
        return $this->code;
    }

    public function type(): CouponType
    {
        return $this->type;
    }

    public function value(): CouponValue
    {
        return $this->value;
    }

    public function minOrderAmount(): CouponMinOrderAmount
    {
        return $this->minOrderAmount;
    }

    public function usageLimit(): CouponUsageLimit
    {
        return $this->usageLimit;
    }

    public function usageLimitPerCustomer(): CouponUsageLimit
    {
        return $this->usageLimitPerCustomer;
    }

    public function usedCount(): int
    {
        return $this->usedCount;
    }

    public function dateRange(): CouponDateRange
    {
        return $this->dateRange;
    }

    public function isActive(): CouponStatus
    {
        return $this->isActive;
    }

    public function applicableCategories(): ?array
    {
        return $this->applicableCategories;
    }

    public function applicableProducts(): ?array
    {
        return $this->applicableProducts;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function activate(): void
    {
        $this->isActive = CouponStatus::active();
    }

    public function deactivate(): void
    {
        $this->isActive = CouponStatus::inactive();
    }

    public function changeCode(CouponCode $code): void
    {
        $this->code = $code;
    }

    public function changeTypeAndValue(CouponType $type, CouponValue $value): void
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function changeMinOrderAmount(CouponMinOrderAmount $minOrderAmount): void
    {
        $this->minOrderAmount = $minOrderAmount;
    }

    public function changeUsageLimit(CouponUsageLimit $usageLimit): void
    {
        $this->usageLimit = $usageLimit;
    }

    public function changeUsageLimitPerCustomer(CouponUsageLimit $usageLimitPerCustomer): void
    {
        $this->usageLimitPerCustomer = $usageLimitPerCustomer;
    }

    public function changeDateRange(CouponDateRange $dateRange): void
    {
        $this->dateRange = $dateRange;
    }

    public function changeApplicableCategories(?array $categories): void
    {
        $this->applicableCategories = $categories;
    }

    public function changeApplicableProducts(?array $products): void
    {
        $this->applicableProducts = $products;
    }

    public function incrementUsage(): void
    {
        $this->usedCount++;
    }

    public function validateUsability(float $subtotal, string $currentDate = 'now'): void
    {
        if (! $this->isActive->value()) {
            throw new InvalidCouponException(sprintf('El cupón "%s" se encuentra inactivo.', $this->code->value()));
        }

        $dateFormatted = date('Y-m-d', strtotime($currentDate));
        if (! $this->dateRange->isDateWithin($dateFormatted)) {
            throw new CouponExpiredException($this->code->value());
        }

        if ($this->usageLimit->value() !== null && $this->usedCount >= $this->usageLimit->value()) {
            throw new CouponUsageLimitReachedException($this->code->value());
        }

        if ($this->minOrderAmount->value() !== null && $subtotal < $this->minOrderAmount->value()) {
            throw new InvalidCouponException(
                sprintf('El monto mínimo para aplicar este cupón es de $%.2f.', $this->minOrderAmount->value())
            );
        }
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type->isPercentage()) {
            $discount = ($subtotal * $this->value->value()) / 100;
        } else {
            $discount = $this->value->value();
        }

        // El descuento no puede exceder el subtotal
        return round(min($subtotal, max(0.0, $discount)), 2);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->value(),
            'code' => $this->code->value(),
            'type' => $this->type->value(),
            'value' => $this->value->value(),
            'min_order_amount' => $this->minOrderAmount->value(),
            'usage_limit' => $this->usageLimit->value(),
            'usage_limit_per_customer' => $this->usageLimitPerCustomer->value(),
            'used_count' => $this->usedCount,
            'valid_from' => $this->dateRange->validFrom(),
            'valid_to' => $this->dateRange->validTo(),
            'is_active' => $this->isActive->value(),
            'applicable_categories' => $this->applicableCategories,
            'applicable_products' => $this->applicableProducts,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
