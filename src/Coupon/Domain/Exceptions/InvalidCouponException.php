<?php

declare(strict_types=1);

namespace Src\Coupon\Domain\Exceptions;

use Exception;

final class InvalidCouponException extends Exception
{
    public function __construct(string $message = 'El cupón ingresado no es válido.', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
