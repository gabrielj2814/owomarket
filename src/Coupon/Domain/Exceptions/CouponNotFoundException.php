<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\Exceptions;

use Exception;

final class CouponNotFoundException extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('No se encontró el cupón "%s".', $identifier), 404);
    }
}
