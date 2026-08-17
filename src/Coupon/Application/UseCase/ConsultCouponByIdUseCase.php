<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Domain\Entities\Coupon;
use Src\Coupon\Domain\Exceptions\CouponNotFoundException;
use Src\Coupon\Domain\ValueObjects\CouponId;

final class ConsultCouponByIdUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository
    ) {}

    public function execute(string $id): Coupon
    {
        $coupon = $this->repository->findById(CouponId::fromString($id));

        if ($coupon === null) {
            throw new CouponNotFoundException($id);
        }

        return $coupon;
    }
}
