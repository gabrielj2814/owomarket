<?php

declare(strict_types=1);

namespace Src\Coupon\Infrastructure\Eloquent\Repositories;

use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Application\DTOs\CouponFilterCriteria;
use Src\Coupon\Application\DTOs\PaginatedCouponsResult;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponDateRange;
use Src\Coupon\Domain\ValueObjects\CouponId;
use Src\Coupon\Domain\ValueObjects\CouponMinOrderAmount;
use Src\Coupon\Domain\ValueObjects\CouponStatus;
use Src\Coupon\Domain\ValueObjects\CouponType;
use Src\Coupon\Domain\ValueObjects\CouponUsageLimit;
use Src\Coupon\Domain\ValueObjects\CouponValue;
use Src\Coupon\Infrastructure\Eloquent\Models\Coupon as EloquentCoupon;

final class CouponRepository implements CouponRepositoryInterface
{
    public function save(Coupon $coupon): Coupon
    {
        $model = EloquentCoupon::create([
            'code' => $coupon->code()->value(),
            'type' => $coupon->type()->value(),
            'value' => $coupon->value()->value(),
            'min_order_amount' => $coupon->minOrderAmount()->value(),
            'usage_limit' => $coupon->usageLimit()->value(),
            'usage_limit_per_customer' => $coupon->usageLimitPerCustomer()->value(),
            'used_count' => $coupon->usedCount(),
            'valid_from' => $coupon->dateRange()->validFrom(),
            'valid_to' => $coupon->dateRange()->validTo(),
            'is_active' => $coupon->isActive()->value(),
            'applicable_categories' => $coupon->applicableCategories(),
            'applicable_products' => $coupon->applicableProducts(),
        ]);

        return $this->toDomain($model);
    }

    public function findById(CouponId $id): ?Coupon
    {
        $model = EloquentCoupon::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(CouponCode $code): ?Coupon
    {
        $model = EloquentCoupon::where('code', $code->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function update(Coupon $coupon): Coupon
    {
        $model = EloquentCoupon::findOrFail($coupon->id()->value());

        $model->update([
            'code' => $coupon->code()->value(),
            'type' => $coupon->type()->value(),
            'value' => $coupon->value()->value(),
            'min_order_amount' => $coupon->minOrderAmount()->value(),
            'usage_limit' => $coupon->usageLimit()->value(),
            'usage_limit_per_customer' => $coupon->usageLimitPerCustomer()->value(),
            'used_count' => $coupon->usedCount(),
            'valid_from' => $coupon->dateRange()->validFrom(),
            'valid_to' => $coupon->dateRange()->validTo(),
            'is_active' => $coupon->isActive()->value(),
            'applicable_categories' => $coupon->applicableCategories(),
            'applicable_products' => $coupon->applicableProducts(),
        ]);

        return $this->toDomain($model->fresh());
    }

    public function delete(CouponId $id): void
    {
        EloquentCoupon::where('id', $id->value())->delete();
    }

    public function filter(CouponFilterCriteria $criteria): PaginatedCouponsResult
    {
        $query = EloquentCoupon::query();

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $search = '%'.trim($criteria->search).'%';
            $query->where('code', 'like', $search);
        }

        if ($criteria->type !== null && trim($criteria->type) !== '') {
            $query->where('type', $criteria->type);
        }

        if ($criteria->isActive !== null) {
            $query->where('is_active', $criteria->isActive);
        }

        if ($criteria->validDate !== null && trim($criteria->validDate) !== '') {
            $date = date('Y-m-d', strtotime($criteria->validDate));
            $query->where('valid_from', '<=', $date)
                ->where('valid_to', '>=', $date);
        }

        $allowedSorts = ['id', 'code', 'type', 'value', 'valid_from', 'valid_to', 'is_active', 'created_at'];
        $sortBy = in_array($criteria->sortBy, $allowedSorts, true) ? $criteria->sortBy : 'created_at';
        $sortDirection = strtolower($criteria->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page
        );

        $items = array_map(
            fn (EloquentCoupon $model) => $this->toDomain($model),
            $paginator->items()
        );

        return new PaginatedCouponsResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    private function toDomain(EloquentCoupon $model): Coupon
    {
        $validFrom = $model->valid_from instanceof \DateTimeInterface
            ? $model->valid_from->format('Y-m-d')
            : (string) substr((string) $model->valid_from, 0, 10);

        $validTo = $model->valid_to instanceof \DateTimeInterface
            ? $model->valid_to->format('Y-m-d')
            : (string) substr((string) $model->valid_to, 0, 10);

        $type = CouponType::fromString($model->type);

        return new Coupon(
            id: CouponId::fromString((string) $model->id),
            code: CouponCode::fromString($model->code),
            type: $type,
            value: CouponValue::create((float) $model->value, $type),
            minOrderAmount: CouponMinOrderAmount::fromNullableFloat($model->min_order_amount !== null ? (float) $model->min_order_amount : null),
            usageLimit: CouponUsageLimit::fromNullableInt($model->usage_limit !== null ? (int) $model->usage_limit : null),
            usageLimitPerCustomer: CouponUsageLimit::fromNullableInt($model->usage_limit_per_customer !== null ? (int) $model->usage_limit_per_customer : null),
            usedCount: (int) ($model->used_count ?? 0),
            dateRange: CouponDateRange::create($validFrom, $validTo),
            isActive: CouponStatus::fromBool((bool) $model->is_active),
            applicableCategories: $model->applicable_categories,
            applicableProducts: $model->applicable_products,
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
