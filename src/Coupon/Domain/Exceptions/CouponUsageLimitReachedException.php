<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\Exceptions;

use Exception;

final class CouponUsageLimitReachedException extends Exception
{
    public function __construct(string $code)
    {
        parent::__construct(sprintf('El cupón "%s" ha alcanzado el límite máximo de usos permitidos.', 400), 400);
    }
}
