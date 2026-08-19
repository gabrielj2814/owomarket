<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Application\DTOs\CouponFilterCriteria;
use Src\Coupon\Application\DTOs\PaginatedCouponsResult;

final class FilterCouponsUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository
    ) {}

    public function execute(CouponFilterCriteria $criteria): PaginatedCouponsResult
    {
        return $this->repository->filter($criteria);
    }
}
