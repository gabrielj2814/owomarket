<?php

declare(strict_types=1);

namespace Src\Review\Domain\Exceptions;

use DomainException;

final class DuplicateReviewException extends DomainException
{
    public static function forCustomerAndProduct(string $customerId, string $productId): self
    {
        return new self("El cliente '{$customerId}' ya ha registrado una reseña para el producto '{$productId}'.");
    }
}
