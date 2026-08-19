<?php

declare(strict_types=1);

namespace Src\Coupon\Application\Contracts;

use Src\Coupon\Application\DTOs\CouponFilterCriteria;
use Src\Coupon\Application\DTOs\PaginatedCouponsResult;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Coupon\Domain\ValueObjects\CouponId;

interface CouponRepositoryInterface
{
    public function save(Coupon $coupon): Coupon;

    public function findById(CouponId $id): ?Coupon;

    public function findByCode(CouponCode $code): ?Coupon;

    public function update(Coupon $coupon): Coupon;

    public function delete(CouponId $id): void;

    public function filter(CouponFilterCriteria $criteria): PaginatedCouponsResult;
}
