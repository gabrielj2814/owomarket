<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\Exceptions;

use Exception;

final class CouponExpiredException extends Exception
{
    public function __construct(string $code)
    {
        parent::__construct(sprintf('El cupón "%s" ha expirado o aún no está vigente.', $code), 400);
    }
}
