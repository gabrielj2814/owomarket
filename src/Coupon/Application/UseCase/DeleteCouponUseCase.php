<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Domain\Exceptions\CouponNotFoundException;
use Src\Coupon\Domain\ValueObjects\CouponId;

final class DeleteCouponUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository
    ) {}

    public function execute(string $id): void
    {
        $couponId = CouponId::fromString($id);
        $coupon = $this->repository->findById($couponId);

        if ($coupon === null) {
            throw new CouponNotFoundException($id);
        }

        $this->repository->delete($couponId);
    }
}
